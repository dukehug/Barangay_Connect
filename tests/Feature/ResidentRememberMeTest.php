<?php

namespace Tests\Feature;

use App\Models\Resident;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ResidentRememberMeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_remember_cookie_expires_in_seven_days_and_restores_a_missing_session(): void
    {
        $this->freezeTime();
        $resident = Resident::factory()->create();
        $cookieName = Auth::guard('resident')->getRecallerName();
        $response = $this->post(route('resident.login.store'), [
            'email' => $resident->email, 'password' => 'password', 'remember' => '1',
        ])->assertRedirect(route('resident.dashboard'))->assertSessionHasNoErrors()->assertCookie($cookieName);
        $cookie = $response->getCookie($cookieName);
        $this->assertSame(now()->addDays(7)->timestamp, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertNotNull($resident->fresh()->remember_token);
        $this->assertAuthenticatedAs($resident, 'resident');

        $this->app['session']->flush();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie->getValue())->get(route('resident.dashboard'))->assertOk();
        $this->assertTrue(Auth::guard('resident')->viaRemember());
        $this->assertAuthenticatedAs($resident, 'resident');

        $this->post(route('resident.logout'))->assertRedirect(route('resident.login'))->assertCookieExpired($cookieName);
        $this->assertGuest('resident');
    }

    public function test_regular_login_does_not_set_a_remember_cookie(): void
    {
        $resident = Resident::factory()->create();
        $this->get(route('resident.login'))->assertOk()->assertSee('Remember me for 7 days');
        $this->post(route('resident.login.store'), [
            'email' => $resident->email, 'password' => 'password',
        ])->assertRedirect(route('resident.dashboard'))->assertCookieMissing(Auth::guard('resident')->getRecallerName());
        $this->assertAuthenticatedAs($resident, 'resident');
        $this->assertNull($resident->fresh()->remember_token);
    }

    public function test_invalid_credentials_do_not_issue_a_remember_cookie(): void
    {
        $resident = Resident::factory()->create();
        $this->post(route('resident.login.store'), [
            'email' => $resident->email, 'password' => 'incorrect', 'remember' => '1',
        ])->assertSessionHasErrors('email')->assertCookieMissing(Auth::guard('resident')->getRecallerName());
        $this->assertGuest('resident');
        $this->assertNull($resident->fresh()->remember_token);
    }
}
