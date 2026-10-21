<?php

namespace Tests\Feature;

use App\Livewire\ResidentDocumentUploader;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use App\Models\ResidentDocumentUpload;
use App\PrivateDocumentUrl;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentDocumentUploaderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('private')->buildTemporaryUrlsUsing(fn (string $path, \DateTimeInterface $expires, array $options = []): string => app(PrivateDocumentUrl::class)($path, $expires, $options));
    }

    public function test_resident_upload_is_private_and_consumed_only_by_its_owner(): void
    {
        $resident = Resident::factory()->create();
        $this->actingAs($resident, 'resident');
        $component = Livewire::test(ResidentDocumentUploader::class, ['kind' => 'photo_id'])
            ->set('data.file', [UploadedFile::fake()->image('identity.png')])->call('saveUpload')->assertHasNoErrors()->assertSet('hasUpload', true);
        $upload = ResidentDocumentUpload::where('token', $component->get('token'))->sole();
        $media = $upload->getFirstMedia('document');
        $this->assertSame('private', $media->disk);
        $path = $media->getPathRelativeToRoot();
        $this->get($media->getTemporaryUrl(now()->addMinutes(30)))->assertOk();
        $type = DocumentType::factory()->create(['category' => 'certification', 'is_active' => true]);
        $data = ['document_type_id' => $type->id, 'purpose' => 'Employment', 'uploads' => ['photo_id' => $upload->token]];
        $this->actingAs(Resident::factory()->create(), 'resident')->post(route('resident.documents.store'), $data)->assertSessionHasErrors();
        $this->get($media->getTemporaryUrl(now()->addMinutes(30)))->assertForbidden();
        $this->actingAs($resident, 'resident')->post(route('resident.documents.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $document = DocumentRequest::where('resident_id', $resident->id)->sole();
        $attachment = $document->attachments()->sole();
        $this->assertSame($path, $attachment->filePath());
        $this->assertSame('identity.png', $attachment->name);
        $this->assertDatabaseMissing('resident_document_uploads', ['id' => $upload->id]);
        Storage::disk('private')->assertExists($path);
        $this->get(route('resident.documents.attachment', $attachment))->assertDownload('identity.png');
        $this->post(route('resident.documents.store'), $data)->assertSessionHasErrors();
    }

    public function test_remove_and_invalid_uploads_do_not_create_submitted_attachments(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        $component = Livewire::test(ResidentDocumentUploader::class, ['kind' => 'photo_id']);
        $component->set('data.file', [UploadedFile::fake()->image('id.png')])->call('saveUpload')->assertHasNoErrors();
        $component->set('data.file', [])->call('saveUpload')->assertHasNoErrors()->assertSet('hasUpload', false);
        $this->assertDatabaseCount('media', 0);
        foreach ([UploadedFile::fake()->image('large.png')->size(10241), UploadedFile::fake()->create('video.mp4', 10, 'video/mp4')] as $file) {
            Livewire::test(ResidentDocumentUploader::class, ['kind' => 'photo_id'])->set('data.file', [$file])->call('saveUpload')->assertHasErrors();
        }
        $this->assertDatabaseCount('request_attachments', 0);
    }

    public function test_drafts_survive_validation_and_expired_drafts_are_cleaned_up(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        $component = Livewire::test(ResidentDocumentUploader::class, ['kind' => 'photo_id'])
            ->set('data.file', [UploadedFile::fake()->image('id.png')])->call('saveUpload')->assertHasNoErrors();
        $token = $component->get('token');
        Livewire::test(ResidentDocumentUploader::class, ['kind' => 'photo_id', 'token' => $token])->assertSet('hasUpload', true)->assertSet('token', $token);
        $path = ResidentDocumentUpload::where('token', $token)->sole()->getFirstMedia('document')->getPathRelativeToRoot();
        $this->travel(25)->hours();
        $this->artisan('model:prune', ['--model' => [ResidentDocumentUpload::class]])->assertSuccessful();
        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseCount('resident_document_uploads', 0);
    }
}
