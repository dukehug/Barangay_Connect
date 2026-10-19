<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Resident;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementRichContentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_can_create_and_edit_a_rich_text_announcement(): void
    {
        $employee = BarangayEmployee::factory()->create();
        $this->actingAs($employee, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $content = '<h2>Community meeting</h2><p>Bring your <strong>resident ID</strong>.</p><ul><li>Registration</li></ul>';

        Livewire::test(CreateAnnouncement::class)->fillForm([
            'title' => 'Community meeting',
            'content' => $content,
            'category' => 'General',
            'published' => true,
        ])->call('create')->assertHasNoFormErrors();

        $announcement = Announcement::sole();
        $this->assertSame($employee->id, $announcement->staff_id);
        $this->assertStringContainsString('<h2>Community meeting</h2>', $announcement->content);
        $this->assertStringContainsString('<strong>resident ID</strong>', $announcement->content);
        $this->assertTrue($announcement->published);
        $this->assertNotNull($announcement->posted_at);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->fillForm(['content' => '<h3>Updated schedule</h3><ol><li>Attend at 9 AM</li></ol>'])
            ->call('save')->assertHasNoFormErrors();

        $this->assertStringContainsString('<h3>Updated schedule</h3>', $announcement->fresh()->content);
        $this->assertStringContainsString('<ol><li>', $announcement->fresh()->content);
    }

    public function test_residents_see_formatted_headings_lists_links_tables_and_images(): void
    {
        $content = '<h2>Health mission</h2><p><strong>Free checkups</strong> for residents.</p>'
            .'<ul><li>Bring your ID</li></ul><ol><li>Register</li></ol>'
            .'<a href="https://example.com/registration">Registration details</a>'
            .'<table><tbody><tr><th>Time</th><td>9 AM</td></tr></tbody></table>'
            .'<img src="/storage/announcements/mission.jpg" alt="Mission poster">';
        $announcement = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay(), 'content' => $content]);
        $this->actingAs(Resident::factory()->create(), 'resident');

        $this->get(route('resident.announcement', $announcement))->assertOk()
            ->assertSee('<h2>Health mission</h2>', false)
            ->assertSee('<strong>Free checkups</strong>', false)
            ->assertSee('<ul><li>Bring your ID</li></ul>', false)
            ->assertSee('<ol><li>Register</li></ol>', false)
            ->assertSee('href="https://example.com/registration"', false)
            ->assertSee('<th>Time</th>', false)
            ->assertSee('src="/storage/announcements/mission.jpg"', false)
            ->assertDontSee('&lt;h2&gt;', false);
    }

    public function test_announcement_html_removes_scripts_event_handlers_and_unsafe_urls(): void
    {
        $announcement = Announcement::factory()->create([
            'published' => true,
            'posted_at' => now()->subDay(),
            'content' => '<h2>Safe heading</h2><script>alert("xss")</script>'
                .'<img src="/storage/announcements/photo.jpg" onerror="alert(1)">'
                .'<a href="javascript:alert(2)" onclick="alert(3)">Link</a>'
                .'<iframe src="https://example.com"></iframe>',
        ]);
        $this->actingAs(Resident::factory()->create(), 'resident');

        $this->get(route('resident.announcement', $announcement))->assertOk()
            ->assertSee('<h2>Safe heading</h2>', false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('onerror=', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('javascript:', false)
            ->assertDontSee('<iframe', false);
    }

    public function test_legacy_plain_text_announcements_keep_their_line_breaks(): void
    {
        $announcement = Announcement::factory()->create([
            'published' => true, 'posted_at' => now()->subDay(),
            'content' => "First line\nSecond line & more",
        ]);
        $this->actingAs(Resident::factory()->create(), 'resident');

        $this->get(route('resident.announcement', $announcement))->assertOk()
            ->assertSee('announcement-content-plain', false)
            ->assertSee("First line\nSecond line &amp; more", false);
    }
}
