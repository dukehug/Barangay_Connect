<?php

namespace Tests\Feature;

use App\Models\Resident;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ResidentSessionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sidebar_profile_link_is_separate_from_the_logout_form(): void
    {
        $resident = Resident::factory()->create();
        $response = $this->actingAs($resident, 'resident')->get('/dashboard')->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $profile = $xpath->query('//a[@class="user-profile"]')->item(0);
        $this->assertSame(route('resident.profile'), $profile->getAttribute('href'));
        $this->assertStringContainsString($resident->first_name, $profile->textContent);
        $this->assertSame(0, $xpath->query('ancestor::form | .//button', $profile)->length);
        $logoutForm = $xpath->query('//form[@class="user-logout"]')->item(0);
        $this->assertSame(route('resident.logout'), $logoutForm->getAttribute('action'));
        $this->assertSame('POST', $logoutForm->getAttribute('method'));
        $this->get($profile->getAttribute('href'))->assertOk();
        $this->assertAuthenticatedAs($resident, 'resident');
    }

    public function test_logout_clears_session_rotates_tokens_and_prevents_access_to_resident_pages(): void
    {
        $resident = Resident::factory()->create();
        $login = $this->post('/login', ['email' => $resident->email, 'password' => 'password', 'remember' => true])
            ->assertRedirect('/dashboard');
        $recallerName = Auth::guard('resident')->getRecallerName();
        $this->withUnencryptedCookie($recallerName, $login->getCookie($recallerName, false)->getValue());
        $this->withSession(['resident_draft' => 'private draft']);
        $oldSessionId = session()->getId();
        $oldCsrfToken = session()->token();
        $oldRememberToken = $resident->fresh()->remember_token;
        $this->post('/logout')->assertRedirect('/')
            ->assertSessionMissing('resident_draft')
            ->assertSessionMissing(Auth::guard('resident')->getName())
            ->assertCookieExpired(Auth::guard('resident')->getRecallerName());
        $this->assertGuest('resident');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldCsrfToken, session()->token());
        $this->assertNotSame($oldRememberToken, $resident->fresh()->remember_token);
        Auth::forgetGuards();
        foreach (['dashboard', 'profile', 'documents', 'complaints', 'activity', 'announcements'] as $page) {
            $this->get('/'.$page)->assertRedirect('/');
        }
    }

    public function test_authenticated_resident_pages_are_not_cached(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        foreach (['dashboard', 'profile', 'documents', 'complaints', 'activity', 'announcements'] as $page) {
            $response = $this->get('/'.$page)->assertOk();
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
            $response->assertHeader('Pragma', 'no-cache');
        }
    }
}
