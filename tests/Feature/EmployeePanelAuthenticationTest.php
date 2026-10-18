<?php

namespace Tests\Feature;

use App\Models\BarangayEmployee;
use App\Models\Resident;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EmployeePanelAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_employee_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_employee_registration_is_not_available(): void
    {
        $this->get('/admin/register')->assertNotFound();
    }

    #[TestWith(['admin'])]
    #[TestWith(['staff'])]
    public function test_allowed_employees_can_sign_in(string $role): void
    {
        $employee = $this->createEmployee($role);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm(['email' => $employee->email, 'password' => 'test-password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($employee, 'employee');
        $this->assertGuest('web');
        $this->assertGuest('resident');
    }

    public function test_dashboard_displays_employee_name_outside_local_environment(): void
    {
        config(['app.env' => 'production']);
        $employee = $this->createEmployee('admin');

        $this->actingAs($employee, 'employee')->get('/admin')
            ->assertOk()->assertSee('Ana Cruz');
    }

    #[TestWith(['resident'])]
    #[TestWith(['Admin'])]
    #[TestWith([''])]
    public function test_invalid_roles_cannot_be_assigned(string $role): void
    {
        $this->expectException(\ValueError::class);
        $this->createEmployee($role);
    }

    public function test_authenticated_disabled_employee_gets_forbidden(): void
    {
        $employee = $this->createEmployee('staff');
        $employee->update(['is_active' => false]);
        $this->actingAs($employee, 'employee')->get('/admin')->assertForbidden();
    }

    public function test_wrong_password_cannot_sign_in(): void
    {
        $employee = $this->createEmployee('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm(['email' => $employee->email, 'password' => 'wrong-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('employee');
    }

    public function test_web_user_credentials_cannot_sign_in_to_employee_panel(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'test-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('employee');
    }

    public function test_resident_guard_authenticates_residents_but_not_employees(): void
    {
        $resident = Resident::create([
            'first_name' => 'Juan', 'last_name' => 'Santos',
            'email' => 'resident@example.test', 'password' => 'test-password',
            'gender' => 'Male', 'birth_date' => '2000-01-02', 'resident_address' => '123 Main Street',
        ]);
        $credentials = ['email' => $resident->email, 'password' => 'test-password'];

        $this->assertTrue(Auth::guard('resident')->attempt($credentials));
        $this->assertAuthenticatedAs($resident, 'resident');
        $this->assertFalse(Auth::guard('employee')->attempt($credentials));
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_web_session_does_not_grant_employee_panel_access(): void
    {
        $this->actingAs(User::factory()->create(), 'web')->get('/admin')
            ->assertRedirect('/admin/login');
    }

    private function createEmployee(string $role): BarangayEmployee
    {
        return BarangayEmployee::create([
            'first_name' => 'Ana', 'last_name' => 'Cruz',
            'email' => 'employee@example.test', 'password' => 'test-password', 'role' => $role,
        ]);
    }
}
