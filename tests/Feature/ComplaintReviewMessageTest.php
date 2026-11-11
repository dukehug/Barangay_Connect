<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\Resident;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComplaintReviewMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resident_progress_shows_the_current_stage_for_every_complaint_status(): void
    {
        foreach (ComplaintStatus::cases() as $status) {
            $complaint = Complaint::factory()->create(['status' => $status]);
            $response = $this->actingAs($complaint->resident, 'resident')
                ->get(route('resident.complaints.show', $complaint))->assertOk();
            $html = new \DOMDocument;
            @$html->loadHTML($response->getContent());
            $xpath = new \DOMXPath($html);
            $expectedStage = $status === ComplaintStatus::Pending ? 'Review' : $status->getLabel();

            $this->assertSame(1, $xpath->query('//li[@aria-current="step"]')->length);
            $this->assertSame($expectedStage, $xpath->evaluate('normalize-space(//li[@aria-current="step"]/strong)'));
            $this->assertSame($status->getLabel(), $xpath->evaluate('normalize-space(//li[@aria-current="step"]/span[@class="document-step-caption"])'));
            $this->assertSame($status === ComplaintStatus::Rejected ? 2 : 4, $xpath->query('//ol[@class="document-stepper"]/li')->length);
            $response->assertSeeInOrder(['Complaint progress', 'Complaint history', 'Complaint details', 'Attachments'])
                ->assertSee('<details class="workflow-card workflow-disclosure document-history" open>', false)
                ->assertSee('Complaint submitted');
        }
    }

    public function test_complaint_timeline_only_shows_its_staff_updates_in_reverse_chronological_order(): void
    {
        $complaint = Complaint::factory()->create(['created_at' => '2026-10-10 08:00:00']);
        $staff = BarangayEmployee::factory()->create();
        $older = AuditLog::create(['table_affected' => 'complaints', 'record_id' => $complaint->id, 'action' => 'updated', 'staff_id' => $staff->id]);
        $older->forceFill(['created_at' => '2026-10-11 09:00:00'])->save();
        $newer = AuditLog::create(['table_affected' => 'complaints', 'record_id' => $complaint->id, 'action' => 'updated', 'staff_id' => $staff->id]);
        $newer->forceFill(['created_at' => '2026-10-12 10:00:00'])->save();
        foreach ([
            ['residents', $complaint->id, 'updated', $staff->id],
            ['complaints', Complaint::factory()->create()->id, 'updated', $staff->id],
            ['complaints', $complaint->id, 'created', $staff->id],
        ] as [$table, $recordId, $action, $staffId]) {
            AuditLog::create(['table_affected' => $table, 'record_id' => $recordId, 'action' => $action, 'staff_id' => $staffId]);
        }

        $response = $this->actingAs($complaint->resident, 'resident')
            ->get(route('resident.complaints.show', $complaint))->assertOk()
            ->assertSeeInOrder(['Complaint history', 'Oct 12, 2026 · 10:00', 'Oct 11, 2026 · 09:00', 'Complaint submitted', 'Oct 10, 2026 · 08:00']);
        $this->assertSame([$newer->id, $older->id], $response->viewData('complaint')->staffUpdates->modelKeys());
        $this->actingAs(Resident::factory()->create(), 'resident')
            ->get(route('resident.complaints.show', $complaint))->assertNotFound();
    }

    public function test_staff_can_save_and_revise_a_message_without_changing_status(): void
    {
        $staff = BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]);
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Pending]);
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->fillForm(['status' => 'in_progress', 'review_note' => 'We are investigating.'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('We are investigating.', $complaint->fresh()->review_note);
        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);

        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->assertFormSet(['review_note' => 'We are investigating.'])
            ->fillForm(['review_note' => '  Please visit the office tomorrow.  '])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('Please visit the office tomorrow.', $complaint->fresh()->review_note);
        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);

        $this->actingAs($complaint->resident, 'resident')
            ->get(route('resident.complaints.show', $complaint))
            ->assertOk()->assertSee('Message from the office')->assertSee('Please visit the office tomorrow.');

        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->fillForm(['review_note' => str_repeat('a', 5001)])
            ->call('save')->assertHasFormErrors(['review_note' => 'max']);
        $this->assertSame('Please visit the office tomorrow.', $complaint->fresh()->review_note);

        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->fillForm(['review_note' => ''])
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull($complaint->fresh()->review_note);
    }

    public function test_message_is_escaped_and_only_visible_to_the_complaint_owner(): void
    {
        $complaint = Complaint::factory()->create(['review_note' => '<script>alert("test")</script>']);
        $this->actingAs($complaint->resident, 'resident')
            ->get(route('resident.complaints.show', $complaint))
            ->assertOk()->assertSee($complaint->review_note)->assertDontSee($complaint->review_note, false);

        $this->actingAs(Resident::factory()->create(), 'resident')
            ->get(route('resident.complaints.show', $complaint))->assertNotFound();
    }
}
