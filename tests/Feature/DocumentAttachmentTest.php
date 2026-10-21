<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Filament\Resources\DocumentRequests\RelationManagers\AttachmentsRelationManager;
use App\Models\BarangayEmployee;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\RequestAttachment;
use App\Models\Resident;
use App\PrivateDocumentUrl;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentAttachmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private')->buildTemporaryUrlsUsing(fn (string $path, \DateTimeInterface $expiration, array $options = []): string => app(PrivateDocumentUrl::class)($path, $expiration, $options));
        Storage::fake('public');
        Storage::fake('local');
    }

    private function attachment(?DocumentRequest $document = null, bool $pdf = false): RequestAttachment
    {
        $document ??= DocumentRequest::factory()->create(['status' => RequestStatus::NeedsRevision]);
        $attachment = $document->attachments()->create(['kind' => 'photo_id', 'name' => $pdf ? 'id.pdf' : 'id.png', 'path' => '', 'order_column' => $document->attachments()->count() + 1]);
        $file = $pdf ? UploadedFile::fake()->createWithContent('id.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF") : UploadedFile::fake()->image('id.png');
        $media = $attachment->addMedia($file)->toMediaCollection('document', 'private');
        $attachment->update(['path' => $media->getPathRelativeToRoot()]);

        return $attachment;
    }

    public function test_preview_is_private_signed_and_expires_after_thirty_minutes(): void
    {
        $attachment = $this->attachment();
        $this->freezeTime();
        $url = $attachment->previewUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $this->assertSame(now()->addMinutes(30)->timestamp, (int) $parameters['expires']);
        $this->assertSame('private', $attachment->getFirstMedia('document')->disk);
        Storage::disk('public')->assertMissing($attachment->filePath());
        $this->get($url)->assertForbidden();
        $this->actingAs(Resident::factory()->create(), 'resident')->get($url)->assertForbidden();
        $this->actingAs($attachment->documentRequest->resident, 'resident')->get($url)
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($url.'&tampered=1')->assertForbidden();
        $this->travel(31)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_staff_can_preview_pdf_but_inactive_staff_cannot(): void
    {
        $attachment = $this->attachment(pdf: true);
        $staff = BarangayEmployee::factory()->create(['is_active' => true]);
        $this->actingAs($staff, 'employee')->get($attachment->previewUrl())->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.requests.attachment', $attachment))->assertDownload('id.pdf');
        $staff->update(['is_active' => false]);
        $this->get($attachment->previewUrl())->assertForbidden();
        $this->get(route('admin.requests.attachment', $attachment))->assertForbidden();
    }

    public function test_signed_urls_cannot_read_arbitrary_private_files(): void
    {
        Storage::disk('private')->put('unrelated.txt', 'secret');
        $this->actingAs(Resident::factory()->create(), 'resident')
            ->get(Storage::disk('private')->temporaryUrl('unrelated.txt', now()->addMinutes(30)))->assertNotFound();
    }

    public function test_resident_upload_rejects_oversized_files_and_videos(): void
    {
        $resident = Resident::factory()->create();
        $type = DocumentType::factory()->create(['category' => 'certification', 'is_active' => true]);
        foreach ([UploadedFile::fake()->image('large.jpg')->size(10241), UploadedFile::fake()->create('movie.mp4', 100, 'video/mp4')] as $file) {
            $this->actingAs($resident, 'resident')->post(route('resident.documents.store'), [
                'document_type_id' => $type->id, 'purpose' => 'Employment', 'files' => ['photo_id' => $file],
            ])->assertSessionHasErrors('files.photo_id');
        }
        $this->assertDatabaseCount('request_attachments', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_deletion_checks_owner_and_workflow_and_removes_media(): void
    {
        $attachment = $this->attachment();
        $path = $attachment->filePath();
        $document = $attachment->documentRequest;
        $this->actingAs(Resident::factory()->create(), 'resident')->delete(route('resident.documents.attachments.destroy', $attachment))->assertNotFound();
        $document->update(['status' => RequestStatus::Pending]);
        $this->actingAs($document->resident, 'resident')->delete(route('resident.documents.attachments.destroy', $attachment))->assertForbidden();
        Storage::disk('private')->assertExists($path);
        $document->update(['status' => RequestStatus::NeedsRevision]);
        $this->delete(route('resident.documents.attachments.destroy', $attachment))->assertRedirect(route('resident.documents.show', $document));
        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseMissing('request_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseHas('request_events', ['document_request_id' => $document->id, 'action' => 'Resident deleted attachment']);
    }

    public function test_reorder_is_persisted_and_scoped_to_the_owner(): void
    {
        $first = $this->attachment();
        $second = $this->attachment($first->documentRequest);
        $foreign = $this->attachment();
        $this->actingAs($first->documentRequest->resident, 'resident')
            ->patch(route('resident.documents.attachments.order', $second), ['direction' => 'up'])->assertRedirect();
        $this->assertSame([$second->id, $first->id], $first->documentRequest->attachments()->pluck('id')->all());
        $this->patch(route('resident.documents.attachments.order', $foreign), ['direction' => 'up'])->assertNotFound();
        $this->assertSame(1, $foreign->fresh()->order_column);
    }

    public function test_import_is_repeatable_and_keeps_legacy_downloads_working(): void
    {
        $document = DocumentRequest::factory()->create();
        $file = UploadedFile::fake()->image('legacy.png');
        $path = $file->store('document-attachments', 'private');
        $attachment = $document->attachments()->create(['kind' => 'photo_id', 'name' => 'legacy.png', 'path' => $path]);
        $this->actingAs($document->resident, 'resident')->get(route('resident.documents.attachment', $attachment))->assertDownload('legacy.png');
        $this->artisan('documents:import-attachments')->assertSuccessful();
        $this->artisan('documents:import-attachments')->assertSuccessful();
        $this->assertDatabaseCount('media', 1);
        Storage::disk('private')->assertExists($path);
        Storage::disk('private')->assertExists($attachment->fresh()->filePath());
        $this->get(route('resident.documents.attachment', $attachment))->assertDownload('legacy.png');
    }

    public function test_admin_gallery_pdf_modal_upload_and_delete_work(): void
    {
        $staff = BarangayEmployee::factory()->create(['role' => 'admin', 'is_active' => true]);
        $attachment = $this->attachment(pdf: true);
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $gallery = Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => $attachment->documentRequest, 'pageClass' => EditDocumentRequest::class]);
        $gallery->assertSuccessful()->assertCanSeeTableRecords([$attachment])
            ->mountTableAction('preview', $attachment)->assertMountedActionModalSeeHtml('iframe')->assertMountedActionModalSeeHtml('private-documents/preview');
        $gallery->unmountAction();
        $gallery->callTableAction('create', data: ['kind' => 'request_letter', 'file' => UploadedFile::fake()->image('letter.png')])->assertHasNoTableActionErrors();
        $this->assertSame(2, $attachment->documentRequest->attachments()->count());
        $new = $attachment->documentRequest->attachments()->where('id', '!=', $attachment->id)->sole();
        $this->assertSame('private', $new->getFirstMedia('document')->disk);
        $gallery->callTableAction('delete', $new)->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing('request_attachments', ['id' => $new->id]);
    }

    public function test_admin_reordering_cannot_move_another_requests_files_and_locked_files_cannot_be_deleted(): void
    {
        $attachment = $this->attachment();
        $second = $this->attachment($attachment->documentRequest);
        $foreign = $this->attachment();
        $this->actingAs(BarangayEmployee::factory()->create(['is_active' => true]), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $gallery = Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => $attachment->documentRequest, 'pageClass' => EditDocumentRequest::class]);
        $gallery->call('reorderTable', [$second->id, $attachment->id, $foreign->id])->assertSuccessful();
        $this->assertSame([$second->id, $attachment->id], $attachment->documentRequest->attachments()->pluck('id')->all());
        $this->assertSame(1, $foreign->fresh()->order_column);
        $attachment->documentRequest->update(['status' => RequestStatus::Pending]);
        $gallery->assertTableActionHidden('delete', $attachment);
        $this->assertDatabaseHas('request_attachments', ['id' => $attachment->id]);
    }

    public function test_admin_upload_rejects_disallowed_files(): void
    {
        $attachment = $this->attachment();
        $this->actingAs(BarangayEmployee::factory()->create(['is_active' => true]), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        foreach ([UploadedFile::fake()->image('large.png')->size(10241), UploadedFile::fake()->create('video.mp4', 50, 'video/mp4')] as $file) {
            Livewire::test(AttachmentsRelationManager::class, ['ownerRecord' => $attachment->documentRequest, 'pageClass' => EditDocumentRequest::class])
                ->callTableAction('create', data: ['kind' => 'photo_id', 'file' => $file])
                ->assertHasTableActionErrors();
        }
        $this->assertDatabaseCount('request_attachments', 1);
    }
}
