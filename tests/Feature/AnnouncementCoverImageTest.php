<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Resident;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnnouncementCoverImageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[DataProvider('coverFormats')]
    public function test_employee_can_create_an_announcement_with_a_cover(string $extension): void
    {
        $employee = BarangayEmployee::factory()->create();
        $this->actingAs($employee, 'employee');

        Livewire::test(CreateAnnouncement::class)->fillForm([
            'title' => 'Community meeting',
            'content' => '<p>Everyone is welcome.</p>',
            'category' => 'General',
            'published' => true,
            'cover' => UploadedFile::fake()->image('cover.'.$extension, 1200, 675),
        ])->call('create')->assertHasNoFormErrors();

        $announcement = Announcement::sole();
        $media = $announcement->getFirstMedia('cover');
        $this->assertNotNull($media);
        $this->assertSame('public', $media->disk);
        $this->assertSame($employee->id, $announcement->staff_id);
        $this->assertCount(1, $announcement->getMedia('cover'));
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());

        Livewire::test(ListAnnouncements::class)->assertSuccessful()
            ->assertCanSeeTableRecords([$announcement])->assertSee($media->getUrl(), false);
    }

    /** @return array<string, array{string}> */
    public static function coverFormats(): array
    {
        return ['JPEG' => ['jpg'], 'PNG' => ['png'], 'WebP' => ['webp']];
    }

    public function test_edit_cover_preview_uses_a_same_origin_url(): void
    {
        $this->actingAs(BarangayEmployee::factory()->create(), 'employee');
        $announcement = Announcement::factory()->create();
        $media = $announcement->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
            ->call('callSchemaComponentMethod', 'form.cover', 'getUploadedFiles')
            ->assertReturned([
                $media->uuid => [
                    'name' => $media->name,
                    'size' => $media->size,
                    'type' => 'image/jpeg',
                    'url' => '/storage/'.$media->getPathRelativeToRoot(),
                ],
            ]);
    }

    public function test_employee_can_keep_replace_and_remove_a_cover(): void
    {
        $this->actingAs(BarangayEmployee::factory()->create(), 'employee');
        $announcement = Announcement::factory()->create();
        $original = $announcement->addMedia(UploadedFile::fake()->image('original.jpg'))->toMediaCollection('cover');
        $originalPath = $original->getPathRelativeToRoot();

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
            ->fillForm(['title' => 'Updated title'])->call('save')->assertHasNoFormErrors();
        $this->assertSame($original->id, $announcement->fresh()->getFirstMedia('cover')->id);
        Storage::disk('public')->assertExists($originalPath);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
            ->fillForm(['cover' => UploadedFile::fake()->image('replacement.png')])
            ->call('save')->assertHasNoFormErrors();

        $replacement = $announcement->fresh()->getFirstMedia('cover');
        $this->assertNotSame($original->id, $replacement->id);
        $this->assertCount(1, $announcement->fresh()->getMedia('cover'));
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($replacement->getPathRelativeToRoot());

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
            ->fillForm(['cover' => []])->call('save')->assertHasNoFormErrors();

        $this->assertFalse($announcement->fresh()->hasMedia('cover'));
        $this->assertSame(asset('resident/images/imus.jpg'), $announcement->fresh()->getFirstMediaUrl('cover'));
        Storage::disk('public')->assertMissing($replacement->getPathRelativeToRoot());
    }

    public function test_invalid_cover_uploads_leave_the_existing_cover_intact(): void
    {
        $this->actingAs(BarangayEmployee::factory()->create(), 'employee');
        $announcement = Announcement::factory()->create();
        $original = $announcement->addMedia(UploadedFile::fake()->image('original.jpg'))->toMediaCollection('cover');

        foreach ([
            UploadedFile::fake()->image('large.jpg')->size(5121),
            UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('animation.gif'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ] as $file) {
            Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
                ->fillForm(['cover' => $file])->call('save')->assertHasFormErrors(['cover']);
            $this->assertSame($original->id, $announcement->fresh()->getFirstMedia('cover')->id);
            Storage::disk('public')->assertExists($original->getPathRelativeToRoot());
        }
    }

    public function test_deleting_an_announcement_removes_its_cover_file(): void
    {
        $this->actingAs(BarangayEmployee::factory()->admin()->create(), 'employee');
        $announcement = Announcement::factory()->create();
        $media = $announcement->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');
        $path = $media->getPathRelativeToRoot();

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->id])
            ->callAction('delete')->assertHasNoActionErrors();

        $this->assertModelMissing($announcement);
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_residents_see_the_cover_on_dashboard_list_and_details(): void
    {
        $announcement = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay()]);
        $media = $announcement->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');
        $this->actingAs(Resident::factory()->create(), 'resident');

        foreach ([route('resident.dashboard'), route('resident.announcements'), route('resident.announcement', $announcement)] as $url) {
            $this->get($url)->assertOk()->assertSee('src="'.$media->getUrl().'"', false);
        }
    }

    public function test_announcements_without_covers_use_default_cards_and_no_detail_banner(): void
    {
        $announcement = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay()]);
        $this->actingAs(Resident::factory()->create(), 'resident');

        foreach ([route('resident.dashboard'), route('resident.announcements')] as $url) {
            $this->get($url)->assertOk()->assertSee('src="'.asset('resident/images/imus.jpg').'"', false);
        }

        $this->get(route('resident.announcement', $announcement))->assertOk()->assertDontSee('class="announcement-cover"', false);
    }

    public function test_covers_do_not_expose_unpublished_or_future_announcements_in_the_portal(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');

        foreach ([['published' => false, 'posted_at' => now()->subDay()], ['published' => true, 'posted_at' => now()->addDay()]] as $attributes) {
            $announcement = Announcement::factory()->create($attributes);
            $media = $announcement->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

            $this->get(route('resident.announcement', $announcement))->assertNotFound();
            $this->get(route('resident.dashboard'))->assertOk()->assertDontSee($media->getUrl(), false);
            $this->get(route('resident.announcements'))->assertOk()->assertDontSee($media->getUrl(), false);
        }
    }
}
