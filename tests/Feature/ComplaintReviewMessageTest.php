<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
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
