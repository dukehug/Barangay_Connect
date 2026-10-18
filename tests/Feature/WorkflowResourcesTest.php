<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Filament\Resources\DocumentRequests\Pages\ListDocumentRequests;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\Resident;
use Database\Seeders\WorkflowDemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ValueError;

class WorkflowResourcesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(WorkflowDemoSeeder::class);
        $this->actingAs(BarangayEmployee::where('email', 'workflow-demo-staff@example.test')->firstOrFail(), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[TestWith([DocumentRequest::class, ListDocumentRequests::class, EditDocumentRequest::class, DocumentRequestResource::class, 'processing', 'ready', 'purpose'])]
    #[TestWith([Complaint::class, ListComplaints::class, EditComplaint::class, ComplaintResource::class, 'in_progress', 'resolved', 'content'])]
    public function test_processing_preserves_original_fields_and_first_handler(string $model, string $list, string $edit, string $resource, string $next, string $later, string $content): void
    {
        $record = $model::where('status', 'pending')->firstOrFail();
        $original = $record->$content;
        $residentId = $record->resident_id;
        $badge = (int) $resource::getNavigationBadge();

        Livewire::test($edit, ['record' => $record->getRouteKey()])
            ->assertActionDoesNotExist('delete')
            ->assertFormFieldIsDisabled('resident_id')
            ->assertFormFieldIsDisabled($content)
            ->assertFormFieldIsEnabled('status')
            ->fillForm(['status' => 'pending'])->call('save')->assertHasNoFormErrors();
        $this->assertNull($record->fresh()->staff_id);

        Livewire::test($edit, ['record' => $record->getRouteKey()])
            ->set('data.'.$content, 'Tampered')
            ->set('data.resident_id', Resident::where('id', '!=', $residentId)->firstOrFail()->id)
            ->set('data.staff_id', 999999)
            ->fillForm(['status' => $next])->call('save')->assertHasNoFormErrors();
        $record->refresh();
        $handler = $record->staff_id;
        $this->assertSame(Filament::auth()->id(), $handler);
        $this->assertSame($original, $record->$content);
        $this->assertSame($residentId, $record->resident_id);
        $this->assertSame($next, $record->status->value);
        $this->assertSame($badge - 1, (int) $resource::getNavigationBadge());

        $second = BarangayEmployee::create(['first_name' => 'Second', 'last_name' => 'Tester', 'email' => 'second@example.test', 'password' => 'testing-password', 'role' => 'staff']);
        $this->actingAs($second, 'employee');
        Livewire::test($edit, ['record' => $record->getRouteKey()])
            ->fillForm(['status' => $later])->call('save')->assertHasNoFormErrors();
        $this->assertSame($handler, $record->fresh()->staff_id);
        $this->assertSame($later, $record->fresh()->status->value);
    }

    #[TestWith([DocumentRequest::class, ListDocumentRequests::class, DocumentRequestResource::class])]
    #[TestWith([Complaint::class, ListComplaints::class, ComplaintResource::class])]
    public function test_lists_search_filter_and_disable_creation_and_deletion(string $model, string $list, string $resource): void
    {
        $pending = $model::where('status', 'pending')->get();
        $assigned = $model::whereNotNull('staff_id')->get();
        Livewire::test($list)->assertSuccessful()
            ->assertSee('Ana DemoSantos')->assertSee('Unassigned')
            ->assertActionDoesNotExist('create')
            ->assertTableActionHidden('delete', $pending->first())
            ->searchTable('DemoCruz')
            ->assertCanSeeTableRecords($pending->where('resident_id', $pending->last()->resident_id))
            ->assertCanNotSeeTableRecords($assigned);
        Livewire::test($list)->filterTable('status', 'pending')
            ->assertCanSeeTableRecords($pending)->assertCanNotSeeTableRecords($assigned);
        Livewire::test($list)->filterTable('unassigned')
            ->assertCanSeeTableRecords($pending)->assertCanNotSeeTableRecords($assigned);
        $this->get($resource::getUrl('index').'/create')->assertNotFound();
        $this->assertFalse($resource::canDelete($pending->first()));
        $this->assertFalse($resource::canDeleteAny());
        $this->assertSame([], Livewire::test($list)->instance()->getTable()->getBulkActions());
    }

    public function test_attachment_is_real_and_demo_seeding_does_not_reset_processed_records(): void
    {
        $record = Complaint::whereNotNull('attachment_url')->firstOrFail();
        Storage::disk('public')->assertExists($record->attachment_url);
        $record->update(['status' => ComplaintStatus::Resolved]);
        $this->seed(WorkflowDemoSeeder::class);
        $this->assertSame(5, Complaint::count());
        $this->assertSame(6, DocumentRequest::count());
        $this->assertSame(ComplaintStatus::Resolved, $record->fresh()->status);
        Livewire::test(EditComplaint::class, ['record' => $record->getRouteKey()])->assertSuccessful();
    }

    #[TestWith([DocumentRequest::class])]
    #[TestWith([Complaint::class])]
    public function test_invalid_status_is_rejected(string $model): void
    {
        $this->expectException(ValueError::class);
        $record = new $model;
        $record->status = 'foo';
    }
}
