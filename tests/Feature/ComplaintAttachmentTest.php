<?php

namespace Tests\Feature;

use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\Resident;
use App\PrivateDocumentUrl;
use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ComplaintAttachmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private')->buildTemporaryUrlsUsing(fn (string $path, DateTimeInterface $expiration, array $options = []): string => app(PrivateDocumentUrl::class)($path, $expiration, $options));
        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[TestWith([false, 'image/png', '<img'])]
    #[TestWith([true, 'application/pdf', '<iframe'])]
    public function test_admin_previews_complaint_files_in_a_modal(bool $pdf, string $mimeType, string $previewElement): void
    {
        $complaint = Complaint::factory()->create();
        $file = $pdf
            ? UploadedFile::fake()->createWithContent('evidence.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF")
            : UploadedFile::fake()->image('evidence.png');
        $media = $complaint->addMedia($file)->toMediaCollection('document');
        $staff = BarangayEmployee::factory()->create(['is_active' => true]);
        $this->actingAs($staff, 'employee');

        Livewire::test(EditComplaint::class, ['record' => $complaint->getRouteKey()])
            ->assertSuccessful()->assertSee($media->file_name)
            ->assertDontSeeHtml('href="'.$media->getUrl().'"')
            ->mountAction('previewAttachment', ['media' => $media->id])
            ->assertActionMounted('previewAttachment')
            ->assertMountedActionModalSeeHtml($previewElement)
            ->assertMountedActionModalSeeHtml('private-documents/preview')
            ->assertMountedActionModalSee('Download');

        $url = Storage::disk('private')->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(30));
        $this->get($url)->assertOk()->assertHeader('Content-Type', $mimeType);
        $staff->update(['is_active' => false]);
        $this->get($url)->assertForbidden();
    }

    #[TestWith(['local'])]
    #[TestWith(['public'])]
    public function test_admin_previews_legacy_attachments_through_an_authenticated_route(string $disk): void
    {
        $path = UploadedFile::fake()->image('legacy.png')->store('complaints', $disk);
        $complaint = Complaint::factory()->create(['attachment_url' => $path]);
        $staff = BarangayEmployee::factory()->create(['is_active' => true]);
        $this->actingAs($staff, 'employee');
        $url = route('admin.complaints.attachment', $complaint);

        Livewire::test(EditComplaint::class, ['record' => $complaint->getRouteKey()])
            ->assertSuccessful()->assertSeeHtml('src="'.$url.'"')
            ->assertDontSeeHtml(Storage::disk('public')->url($path))
            ->mountAction('previewAttachment')
            ->assertActionMounted('previewAttachment')
            ->assertMountedActionModalSeeHtml('<img')
            ->assertMountedActionModalSeeHtml($url);

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $staff->update(['is_active' => false]);
        $this->get($url)->assertForbidden();
    }

    public function test_guests_and_residents_cannot_access_the_staff_attachment_route(): void
    {
        $path = UploadedFile::fake()->image('legacy.png')->store('complaints', 'local');
        $complaint = Complaint::factory()->create(['attachment_url' => $path]);
        $url = route('admin.complaints.attachment', $complaint);

        $this->get($url)->assertRedirect();
        $this->actingAs($complaint->resident, 'resident')->get($url)->assertRedirect();
    }

    public function test_preview_action_cannot_select_media_from_another_complaint(): void
    {
        $complaint = Complaint::factory()->create();
        $otherComplaint = Complaint::factory()->create();
        $media = $otherComplaint->addMedia(UploadedFile::fake()->image('other.png'))->toMediaCollection('document');
        $this->actingAs(BarangayEmployee::factory()->create(['is_active' => true]), 'employee');

        Livewire::test(EditComplaint::class, ['record' => $complaint->getRouteKey()])
            ->mountAction('previewAttachment', ['media' => $media->id])->assertNotFound();
    }

    public function test_empty_gallery_and_missing_legacy_files_are_handled(): void
    {
        $complaint = Complaint::factory()->create();
        $this->actingAs(BarangayEmployee::factory()->create(['is_active' => true]), 'employee');

        Livewire::test(EditComplaint::class, ['record' => $complaint->getRouteKey()])
            ->assertSuccessful()->assertSee('No uploaded attachments.');
        $this->get(route('admin.complaints.attachment', $complaint))->assertNotFound();
        $complaint->update(['attachment_url' => 'complaints/missing.png']);
        $this->get(route('admin.complaints.attachment', $complaint))->assertNotFound();
    }

    public function test_resident_cannot_preview_another_residents_complaint_media(): void
    {
        $complaint = Complaint::factory()->create();
        $media = $complaint->addMedia(UploadedFile::fake()->image('private.png'))->toMediaCollection('document');
        $url = Storage::disk('private')->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(30));

        $this->actingAs(Resident::factory()->create(), 'resident')->get($url)->assertForbidden();
        $this->actingAs($complaint->resident, 'resident')->get($url)->assertOk();
    }
}
