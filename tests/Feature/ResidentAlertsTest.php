<?php

namespace Tests\Feature;

use App\Models\Resident;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResidentAlertsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_document_and_complaint_notices_use_the_shared_sweetalert(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        foreach (['documents' => 'document_submitted', 'complaints' => 'complaint_submitted'] as $page => $flag) {
            $response = $this->withSession([$flag => true])->get('/'.$page)->assertOk();
            $response->assertSee('https://cdn.jsdelivr.net/npm/sweetalert2@11', false)
                ->assertSee('resident/js/resident-alerts.js', false)
                ->assertSee('data-title="Submission Successful"', false);
            $this->assertSame(1, substr_count($response->getContent(), 'data-resident-alert'));
        }
    }

    public function test_validation_errors_are_not_duplicated_in_resident_dialogs(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        foreach (['complaints' => 'POST', 'profile' => 'PATCH'] as $page => $method) {
            $redirect = $this->from('/'.$page)->call($method, '/'.$page, [])->assertSessionHasErrors();
            $cookieName = config('session.cookie');
            $response = $this->withUnencryptedCookie($cookieName, $redirect->getCookie($cookieName, false)->getValue())
                ->get('/'.$page)->assertOk();
            $response->assertSee('data-icon="error"', false)->assertSee('data-reopen', false);
            $this->assertSame(1, substr_count($response->getContent(), 'data-resident-alert'));
        }
    }

    public function test_regular_status_messages_use_sweetalert_on_supported_pages(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident')
            ->withSession(['status' => 'Your response has been recorded.'])
            ->get('/documents')->assertOk()->assertSee('data-message="Your response has been recorded."', false);
        $this->withSession(['status' => '<script>alert("unsafe")</script>'])
            ->get('/documents')->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
        $response = $this->withSession(['status' => 'Your profile has been updated.'])
            ->get('/profile')->assertOk()
            ->assertSee('https://cdn.jsdelivr.net/npm/sweetalert2@11', false)
            ->assertSee('resident/js/resident-alerts.js', false)
            ->assertSee('data-title="Profile updated"', false)
            ->assertSee('data-message="Your profile has been updated."', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-resident-alert'));
        $this->get('/dashboard')->assertOk()->assertDontSee('resident/js/resident-alerts.js', false);
    }
}
