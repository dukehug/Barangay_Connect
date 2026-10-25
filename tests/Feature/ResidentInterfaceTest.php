<?php

namespace Tests\Feature;

use App\Livewire\ResidentBirthDatePicker;
use App\Models\Announcement;
use App\Models\Resident;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentInterfaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_birth_date_picker_restores_valid_old_input_and_ignores_invalid_dates(): void
    {
        Livewire::test(ResidentBirthDatePicker::class, ['value' => '1995-06-12'])
            ->assertSet('data.birth_date', '1995-06-12 00:00:00')
            ->assertSee('Birthdate');
        Livewire::test(ResidentBirthDatePicker::class, ['value' => 'not-a-date'])
            ->assertSet('data.birth_date', null);
        $this->withSession(['_old_input' => ['birth_date' => '1995-06-12']])
            ->get('/register')->assertOk()->assertSee('1995-06-12');
    }

    public function test_registration_accepts_the_date_format_posted_by_the_picker(): void
    {
        $data = Resident::factory()->make()->only(['first_name', 'last_name', 'email', 'gender', 'resident_address', 'marital_status', 'blood_type']);
        $this->post('/register', [
            ...$data,
            'birth_date' => '1995-06-12',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasNoErrors()->assertRedirect('/register');
        $this->assertSame('1995-06-12', Resident::where('email', $data['email'])->sole()->birth_date->format('Y-m-d'));
    }

    public function test_dashboard_reads_hotline_settings_and_shows_published_announcements_with_covers(): void
    {
        config()->set('barangay.hotlines.barangay', [['label' => 'Local emergency desk', 'number' => '09170000000']]);
        config()->set('barangay.hotlines.national', [[
            'name' => 'Emergency service',
            'numbers' => [['label' => 'Emergency number', 'number' => '911']],
        ]]);
        $announcement = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay()]);
        $draft = Announcement::factory()->create(['published' => false]);
        $this->actingAs(Resident::factory()->create(), 'resident')->get('/dashboard')
            ->assertOk()->assertSee('Emergency Hotlines')
            ->assertSee('Local emergency desk')->assertSee('tel:09170000000', false)
            ->assertSee('Emergency service')->assertSee('tel:911', false)
            ->assertSee($announcement->title)->assertDontSee($draft->title)
            ->assertSee('resident/images/imus.jpg', false)
            ->assertSee('href="'.route('resident.profile').'"', false)
            ->assertSee('(Resident)')->assertSee('aria-label="Log out"', false)
            ->assertDontSee('My Profile');
    }
}
