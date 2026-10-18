<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Database\Seeders\WorkflowDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function prepareDemo(): BarangayEmployee
    {
        Storage::fake('public');
        $this->seed(WorkflowDemoSeeder::class);

        return BarangayEmployee::firstOrFail();
    }

    private function announcement(BarangayEmployee $staff): Announcement
    {
        return Announcement::create(['staff_id' => $staff->id, 'title' => 'Demo', 'content' => 'Content', 'category' => 'General']);
    }

    public function test_employee_create_update_and_delete_each_log_once_and_noop_does_not(): void
    {
        $staff = $this->prepareDemo();
        $this->actingAs($staff, 'employee');
        $announcement = $this->announcement($staff);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['staff_id' => $staff->id, 'action' => 'created', 'table_affected' => 'announcements', 'record_id' => $announcement->id]);
        $announcement->update(['title' => 'Changed']);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->travel(2)->seconds();
        $announcement->save();
        $announcement->touch();
        $this->assertDatabaseCount('audit_logs', 2);
        $announcement->delete();
        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertSame(['created', 'updated', 'deleted'], AuditLog::orderBy('id')->pluck('action')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'deleted', 'record_id' => $announcement->id]);
    }

    #[TestWith([BarangayEmployee::class, 'first_name'])]
    #[TestWith([Resident::class, 'first_name'])]
    #[TestWith([DocumentType::class, 'document_type_description'])]
    #[TestWith([DocumentRequest::class, 'purpose'])]
    #[TestWith([Complaint::class, 'content'])]
    public function test_other_models_record_employee_updates(string $model, string $attribute): void
    {
        $staff = $this->prepareDemo();
        $this->actingAs($staff, 'employee');
        $record = $model::firstOrFail();
        $record->update([$attribute => 'Changed']);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['staff_id' => $staff->id, 'action' => 'updated', 'table_affected' => $record->getTable(), 'record_id' => $record->id]);
    }

    public function test_guest_and_resident_operations_are_not_logged(): void
    {
        $staff = $this->prepareDemo();
        $this->assertDatabaseCount('audit_logs', 0);
        $announcement = $this->announcement($staff);
        $announcement->update(['title' => 'Guest update']);
        $announcement->delete();
        $this->actingAs(Resident::firstOrFail(), 'resident');
        Resident::firstOrFail()->update(['first_name' => 'Resident edit']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_remember_token_is_ignored_but_profile_and_password_changes_are_logged(): void
    {
        $staff = $this->prepareDemo();
        $this->actingAs($staff, 'employee');
        $staff->forceFill(['remember_token' => 'new-token'])->save();
        $this->assertDatabaseCount('audit_logs', 0);
        $staff->forceFill(['remember_token' => 'another-token', 'first_name' => 'Changed'])->save();
        $this->assertDatabaseCount('audit_logs', 1);
        $staff->update(['password' => 'new-test-password']);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertStringNotContainsString('new-test-password', AuditLog::all()->toJson());
        $this->assertStringNotContainsString('another-token', AuditLog::all()->toJson());
    }

    public function test_remembered_login_does_not_create_employee_update_log(): void
    {
        $staff = $this->prepareDemo();
        $staff->update(['password' => 'test-password']);
        $this->assertTrue(Auth::guard('employee')->attempt(['email' => $staff->email, 'password' => 'test-password'], true));
        Auth::guard('employee')->logout();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_processing_status_and_assigning_staff_produces_one_update_log(): void
    {
        $staff = $this->prepareDemo();
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $record = DocumentRequest::where('status', 'pending')->firstOrFail();
        Livewire::test(EditDocumentRequest::class, ['record' => $record->id])
            ->fillForm(['status' => 'processing'])->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['staff_id' => $staff->id, 'action' => 'updated', 'table_affected' => 'document_requests', 'record_id' => $record->id]);
    }

    public function test_audit_log_itself_is_not_observed(): void
    {
        $staff = $this->prepareDemo();
        $this->actingAs($staff, 'employee');
        AuditLog::create(['staff_id' => $staff->id, 'action' => 'created', 'table_affected' => 'announcements', 'record_id' => 123]);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_read_only_list_sorts_and_filters_records(): void
    {
        $staff = $this->prepareDemo();
        $staff->update(['role' => EmployeeRole::Admin]);
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $announcement = $this->announcement($staff);
        $created = AuditLog::latest('id')->firstOrFail();
        $this->travel(2)->seconds();
        $announcement->update(['title' => 'Changed']);
        $updated = AuditLog::latest('id')->firstOrFail();
        Livewire::test(ListAuditLogs::class)->assertSuccessful()
            ->assertCanSeeTableRecords([$updated, $created], inOrder: true)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete')
            ->filterTable('action', 'updated')
            ->assertCanSeeTableRecords([$updated])->assertCanNotSeeTableRecords([$created])
            ->filterTable('table_affected', 'residents')->assertCanNotSeeTableRecords([$updated])
            ->resetTableFilters()->filterTable('staff', $staff->id)->assertCanSeeTableRecords([$created, $updated]);
        $this->assertFalse(AuditLogResource::canEdit($created));
        $this->assertFalse(AuditLogResource::canDelete($created));
        $this->assertFalse(AuditLogResource::canDeleteAny());
        $this->get('/admin/audit-logs/create')->assertNotFound();
        $this->get('/admin/audit-logs/'.$created->id.'/edit')->assertNotFound();
    }
}
