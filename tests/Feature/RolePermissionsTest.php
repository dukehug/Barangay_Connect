<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Resources\BarangayEmployees\Pages\EditBarangayEmployee;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Database\Seeders\WorkflowDemoSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function prepareDemo(): array
    {
        Storage::fake('public');
        $this->seed(WorkflowDemoSeeder::class);
        $staff = BarangayEmployee::firstOrFail();
        $admin = BarangayEmployee::create(['first_name' => 'Admin', 'last_name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'test-password', 'role' => EmployeeRole::Admin]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return [$admin, $staff];
    }

    #[TestWith(['admin'])]
    #[TestWith(['staff'])]
    public function test_complete_policy_matrix(string $role): void
    {
        [$admin, $staff] = $this->prepareDemo();
        $user = $role === 'admin' ? $admin : $staff;
        $isAdmin = $user->isAdmin();
        $announcement = Announcement::create(['staff_id' => $admin->id, 'title' => 'Test', 'content' => 'Demo', 'category' => 'General']);
        $log = AuditLog::create(['staff_id' => $admin->id, 'action' => 'created', 'table_affected' => 'announcements', 'record_id' => $announcement->id]);
        $cases = [
            [$announcement, true, true, true, $isAdmin],
            [Complaint::firstOrFail(), true, false, true, $isAdmin],
            [DocumentRequest::firstOrFail(), true, false, true, $isAdmin],
            [DocumentType::firstOrFail(), true, true, true, false],
            [Resident::firstOrFail(), true, false, false, false],
            [$staff, $isAdmin, $isAdmin, $isAdmin, false],
            [$log, $isAdmin, false, false, false],
        ];
        foreach ($cases as [$record, $view, $create, $update, $delete]) {
            $gate = Gate::forUser($user);
            $this->assertSame($view, $gate->allows('viewAny', $record::class), $record::class.' viewAny');
            $this->assertSame($view, $gate->allows('view', $record));
            $this->assertSame($create, $gate->allows('create', $record::class));
            $this->assertSame($update, $gate->allows('update', $record));
            $this->assertSame($delete, $gate->allows('delete', $record));
            $this->assertFalse($gate->allows('deleteAny', $record::class));
        }
        $unusedType = DocumentType::create(['document_type_name' => 'Unused']);
        $unusedResident = Resident::firstOrFail()->replicate();
        $unusedResident->email = 'unused@example.test';
        $unusedResident->save();
        $this->assertSame($isAdmin, Gate::forUser($user)->allows('delete', $unusedType));
        $this->assertSame($isAdmin, Gate::forUser($user)->allows('delete', $unusedResident));
        $unusedResident->complaints()->create(['complaint_type' => 'Noise', 'content' => 'Demo']);
        $this->assertFalse(Gate::forUser($user)->allows('delete', $unusedResident));
    }

    #[TestWith(['/admin/barangay-employees'])]
    #[TestWith(['/admin/audit-logs'])]
    public function test_staff_cannot_open_admin_only_pages(string $url): void
    {
        [$admin, $staff] = $this->prepareDemo();
        $this->actingAs($staff, 'employee')->get($url)->assertForbidden();
    }

    #[TestWith(['admin'])]
    #[TestWith(['staff'])]
    public function test_sidebar_matches_role_and_residents_are_read_only(string $role): void
    {
        [$admin, $staff] = $this->prepareDemo();
        $this->actingAs($role === 'admin' ? $admin : $staff, 'employee');
        $response = $this->get('/admin');
        $response->assertOk()->assertSee('/admin/residents')->assertSee('/admin/announcements')->assertSee('/admin/document-types')->assertSee('/admin/document-requests')->assertSee('/admin/complaints');
        if ($role === 'staff') {
            $response->assertDontSee('/admin/barangay-employees')->assertDontSee('/admin/audit-logs');
        } else {
            $response->assertSee('/admin/barangay-employees')->assertSee('/admin/audit-logs');
        }
        $this->get('/admin/residents/'.Resident::firstOrFail()->id.'/edit')->assertNotFound();
    }

    public function test_admin_cannot_demote_or_disable_self_even_with_tampered_state(): void
    {
        [$admin] = $this->prepareDemo();
        $this->actingAs($admin, 'employee');
        $hash = $admin->password;
        Livewire::test(EditBarangayEmployee::class, ['record' => $admin->id])
            ->assertFormFieldIsDisabled('role')->assertFormFieldIsDisabled('is_active')
            ->assertFormSet(['password' => null])
            ->set('data.role', 'staff')->set('data.is_active', false)
            ->fillForm(['first_name' => 'Renamed', 'password' => ''])
            ->call('save')->assertHasNoFormErrors();
        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
        $this->assertSame($hash, $admin->password);
        $this->assertSame('Renamed', $admin->first_name);
    }

    public function test_admin_can_edit_another_employee_and_audit_the_change(): void
    {
        [$admin, $staff] = $this->prepareDemo();
        $this->actingAs($admin, 'employee');
        Livewire::test(EditBarangayEmployee::class, ['record' => $staff->id])
            ->assertFormFieldIsEnabled('role')->assertFormFieldIsEnabled('is_active')
            ->fillForm(['role' => 'admin', 'is_active' => false, 'password' => 'replacement-password'])
            ->call('save')->assertHasNoFormErrors();
        $staff->refresh();
        $this->assertTrue($staff->isAdmin());
        $this->assertFalse($staff->is_active);
        $this->assertTrue(Hash::check('replacement-password', $staff->password));
        $this->assertDatabaseHas('audit_logs', ['staff_id' => $admin->id, 'table_affected' => 'barangay_employees', 'record_id' => $staff->id, 'action' => 'updated']);
    }

    public function test_disabled_login_is_rejected_and_reenabled_login_succeeds(): void
    {
        [$admin, $staff] = $this->prepareDemo();
        $staff->update(['password' => 'test-password', 'is_active' => false]);
        Livewire::test(Login::class)->fillForm(['email' => $staff->email, 'password' => 'test-password'])
            ->call('authenticate')->assertHasFormErrors(['email']);
        $this->assertGuest('employee');
        $staff->update(['is_active' => true]);
        Livewire::test(Login::class)->fillForm(['email' => $staff->email, 'password' => 'test-password'])
            ->call('authenticate')->assertHasNoFormErrors()->assertRedirect();
        $this->assertAuthenticatedAs($staff, 'employee');
    }

    public function test_admin_deletes_unused_type_and_logs_deletion_but_linked_type_is_protected(): void
    {
        [$admin] = $this->prepareDemo();
        $this->actingAs($admin, 'employee');
        $used = DocumentType::firstOrFail();
        Livewire::test(EditDocumentType::class, ['record' => $used->id])->assertActionHidden('delete');
        $unused = DocumentType::create(['document_type_name' => 'Unused']);
        Livewire::test(EditDocumentType::class, ['record' => $unused->id])->callAction('delete');
        $this->assertModelMissing($unused);
        $this->assertDatabaseHas('audit_logs', ['staff_id' => $admin->id, 'action' => 'deleted', 'table_affected' => 'document_types', 'record_id' => $unused->id]);
    }

    public static function resourceRoles(): array
    {
        $cases = [];
        foreach (['admin', 'staff'] as $role) {
            foreach (['Announcements' => Announcement::class, 'Complaints' => Complaint::class, 'DocumentRequests' => DocumentRequest::class, 'DocumentTypes' => DocumentType::class, 'Residents' => Resident::class, 'BarangayEmployees' => BarangayEmployee::class, 'AuditLogs' => AuditLog::class] as $resource => $model) {
                $cases[$role.' '.$resource] = [$role, $resource, $model];
            }
        }

        return $cases;
    }

    #[DataProvider('resourceRoles')]
    public function test_list_actions_follow_policy(string $role, string $resource, string $model): void
    {
        [$admin, $staff] = $this->prepareDemo();
        Announcement::create(['staff_id' => $admin->id, 'title' => 'Demo', 'content' => 'Test', 'category' => 'General']);
        AuditLog::create(['staff_id' => $admin->id, 'action' => 'created', 'table_affected' => 'announcements', 'record_id' => 1]);
        $actor = $role === 'admin' ? $admin : $staff;
        $this->actingAs($actor, 'employee');
        $page = 'App\\Filament\\Resources\\'.$resource.'\\Pages\\List'.$resource;
        $component = Livewire::test($page);
        if ($role === 'staff' && in_array($resource, ['BarangayEmployees', 'AuditLogs'], true)) {
            $component->assertForbidden();

            return;
        }

        $record = $model::firstOrFail();
        $component->assertSuccessful()->assertCanSeeTableRecords([$record]);
        $this->assertSame([], $component->instance()->getTable()->getBulkActions());
        if (in_array($resource, ['BarangayEmployees', 'AuditLogs'], true)) {
            $component->assertTableActionDoesNotExist('delete');
        } elseif (Gate::forUser($actor)->allows('delete', $record)) {
            $component->assertTableActionVisible('delete', $record);
        } else {
            $component->assertTableActionHidden('delete', $record);
        }
        if (in_array($resource, ['Residents', 'AuditLogs'], true)) {
            $component->assertTableActionDoesNotExist('edit');
        } else {
            $component->assertTableActionVisible('edit', $record);
        }
        if (in_array($resource, ['Announcements', 'DocumentTypes', 'BarangayEmployees'], true)) {
            $component->assertActionVisible('create');
        } else {
            $component->assertActionDoesNotExist('create');
        }
    }
}
