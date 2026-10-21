<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\RequestReference;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RequestReferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_references_are_unique_across_both_types_and_stable_after_updates(): void
    {
        $document = DocumentRequest::factory()->create(['created_at' => '2026-10-15 12:00:00']);
        $complaint = Complaint::factory()->create(['created_at' => '2026-10-15 12:00:00']);
        $this->assertMatchesRegularExpression('/^DOC-2026-10-\d{4}$/', $document->reference);
        $this->assertMatchesRegularExpression('/^DOC-2026-10-\d{4}$/', $complaint->reference);
        $this->assertNotSame($document->reference, $complaint->reference);
        $original = $complaint->reference;
        $complaint->update(['review_note' => 'Updated message']);
        $this->assertSame($original, $complaint->fresh()->reference);

        foreach ([$document, $complaint] as $record) {
            $section = $record instanceof Complaint ? 'complaints' : 'documents';
            $this->actingAs($record->resident, 'resident')
                ->get(route('resident.'.$section, ['search' => $record->reference]))
                ->assertOk()->assertSee($record->reference);
            $this->get(route('resident.'.$section.'.show', $record))->assertOk()->assertSee($record->reference);
        }
    }

    public function test_generator_uses_the_remaining_number_without_reusing_existing_references(): void
    {
        $references = [];
        for ($number = 0; $number <= 9999; $number++) {
            if ($number !== 42) {
                $references[] = ['reference' => 'DOC-2026-01-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT)];
            }
        }
        foreach (array_chunk($references, 500) as $chunk) {
            DB::table('request_references')->insert($chunk);
        }
        $this->assertSame('DOC-2026-01-0042', app(RequestReference::class)->generate('2026-01-15'));
        $this->expectException(\RuntimeException::class);
        app(RequestReference::class)->generate('2026-01-15');
    }

    public function test_existing_records_receive_references_without_changing_their_ids_or_timestamps(): void
    {
        $document = DocumentRequest::factory()->create(['created_at' => '2025-12-10 12:00:00']);
        $complaint = Complaint::factory()->create(['created_at' => '2026-01-10 12:00:00']);
        $migration = require database_path('migrations/2026_10_21_213737_add_references_to_document_requests_and_complaints.php');
        $migration->down();
        $migration->up();

        $this->assertMatchesRegularExpression('/^DOC-2025-12-\d{4}$/', $document->fresh()->reference);
        $this->assertMatchesRegularExpression('/^DOC-2026-01-\d{4}$/', $complaint->fresh()->reference);
        $this->assertSame($document->updated_at->toDateTimeString(), $document->fresh()->updated_at->toDateTimeString());
        $this->assertSame($complaint->updated_at->toDateTimeString(), $complaint->fresh()->updated_at->toDateTimeString());
    }

    public function test_complaint_updated_by_tracks_the_latest_editor_including_message_only_updates(): void
    {
        $first = BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]);
        $second = BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]);
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Pending]);
        $this->assertNull($complaint->latestStaffUpdate);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($first, 'employee');
        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->fillForm(['status' => 'in_progress'])->call('save')->assertHasNoFormErrors();
        $this->assertSame($first->id, $complaint->fresh()->latestStaffUpdate->staff_id);

        $this->actingAs($second, 'employee');
        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->fillForm(['review_note' => 'Please visit the office.'])->call('save')->assertHasNoFormErrors();
        AuditLog::create(['staff_id' => $first->id, 'action' => 'updated', 'table_affected' => 'residents', 'record_id' => $complaint->id]);
        $this->assertSame($second->id, $complaint->fresh()->latestStaffUpdate->staff_id);
        $this->assertSame($first->id, $complaint->fresh()->staff_id);
        Livewire::test(ListComplaints::class)->searchTable($complaint->reference)
            ->assertCanSeeTableRecords([$complaint])->assertSee($second->getFilamentName());
    }
}
