<?php

namespace Tests\Feature;

use App\ConfirmationDeadline;
use App\DocumentWorkflow;
use App\Enums\RequestStatus;
use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Filament\Resources\DocumentRequests\Pages\ListDocumentRequests;
use App\Filament\Resources\ResourceItems\Pages\ManageResourceItems;
use App\Models\BarangayEmployee;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\RequestReservation;
use App\Models\Resident;
use App\Models\ResourceItem;
use App\Models\WorkflowSetting;
use App\PrivateDocumentUrl;
use App\ResourceAvailability;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private DocumentWorkflow $workflow;

    private BarangayEmployee $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-23 15:00', 'Asia/Manila'));
        Storage::fake('local');
        Storage::fake('private')->buildTemporaryUrlsUsing(fn (string $path, \DateTimeInterface $expiration, array $options = []): string => app(PrivateDocumentUrl::class)($path, $expiration, $options));
        $this->workflow = app(DocumentWorkflow::class);
        $this->staff = BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    private function application(string $category = 'use_closure', RequestStatus $status = RequestStatus::Pending): DocumentRequest
    {
        return DocumentRequest::factory()->create(['category' => $category, 'status' => $status, 'staff_id' => null]);
    }

    private function reservation(DocumentRequest $request, ?ResourceItem $resource = null, int $quantity = 10): RequestReservation
    {
        return RequestReservation::factory()->create([
            'document_request_id' => $request->id, 'resource_item_id' => $resource?->id ?? ResourceItem::factory()->create()->id,
            'requested_quantity' => $quantity, 'approved_quantity' => 0,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function rejectedReview(DocumentRequest $request, array $data, string $field): void
    {
        try {
            $this->workflow->review($request, $data, $this->staff);
            $this->fail('Expected validation to reject the transition.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    public function test_deadline_skips_weekends_preserves_minutes_and_uses_philippine_time(): void
    {
        $deadline = app(ConfirmationDeadline::class);
        $this->assertSame('2026-10-27 15:00', $deadline->calculate(CarbonImmutable::now(), 48)->setTimezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-28 00:00', $deadline->calculate(CarbonImmutable::parse('2026-10-24 10:23', 'Asia/Manila'), 48)->setTimezone('Asia/Manila')->format('Y-m-d H:i'));
        $this->assertSame('2026-10-26 01:30', $deadline->calculate(CarbonImmutable::parse('2026-10-23 23:30', 'Asia/Manila'), 2)->setTimezone('Asia/Manila')->format('Y-m-d H:i'));
    }

    public function test_clearance_requires_office_receipt_before_printing_and_cannot_skip_steps(): void
    {
        $request = $this->application('clearance');
        $this->rejectedReview($request, ['status' => 'ready'], 'status');
        $this->workflow->review($request, ['status' => 'awaiting_payment'], $this->staff);
        $this->rejectedReview($request, ['status' => 'processing'], 'official_receipt');
        $this->workflow->review($request, ['status' => 'processing', 'official_receipt' => 'OR-123', 'amount_paid' => 50], $this->staff);
        $request->refresh();
        $this->assertNotNull($request->paid_at);
        $this->assertSame('50.00', $request->amount_paid);
        $this->workflow->review($request, ['status' => 'ready'], $this->staff);
        $this->workflow->review($request, ['status' => 'released'], $this->staff);
        $this->assertSame(RequestStatus::Released, $request->fresh()->status);
        $this->assertSame(4, $request->events()->count());
    }

    public function test_certification_requires_manual_rbi_verification_and_officer_signature(): void
    {
        $request = $this->application('certification');
        $this->rejectedReview($request, ['status' => 'processing'], 'rbi_verified');
        $this->workflow->review($request, ['status' => 'processing', 'rbi_verified' => true], $this->staff);
        $this->assertNotNull($request->fresh()->rbi_verified_at);
        $this->rejectedReview($request, ['status' => 'ready'], 'status');
        $this->workflow->review($request, ['status' => 'awaiting_signature'], $this->staff);
        $this->workflow->review($request, ['status' => 'ready'], $this->staff);
        $this->assertSame(RequestStatus::Ready, $request->fresh()->status);
    }

    public function test_partial_offer_reserves_only_offered_quantities_until_resident_accepts(): void
    {
        $request = $this->application();
        $line = $this->reservation($request);
        $street = $this->reservation($request, ResourceItem::factory()->create(['kind' => 'street', 'quantity' => 1]), 1);
        $allocations = [['id' => $line->id, 'approved_quantity' => 6], ['id' => $street->id, 'approved_quantity' => 1]];
        $this->rejectedReview($request, ['status' => 'approved', 'allocations' => $allocations], 'status');
        $this->assertSame(0, $line->fresh()->approved_quantity);
        $this->workflow->review($request, ['status' => 'awaiting_confirmation', 'review_note' => 'Four chairs unavailable.', 'allocations' => $allocations], $this->staff);
        $deadline = $request->fresh()->confirmation_expires_at;
        WorkflowSetting::current()->update(['confirmation_hours' => 1]);
        $this->assertTrue($deadline->equalTo($request->fresh()->confirmation_expires_at));
        $availability = app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at);
        $this->assertSame(94, $availability['available']);
        $this->actingAs($request->resident, 'resident')->get(route('resident.documents.show', $request))->assertOk()->assertSee('Four chairs unavailable.')->assertSee('Accept arrangement');
        $this->post(route('resident.documents.respond', $request), ['action' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame(RequestStatus::Approved, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->accepted_at);
        $this->assertSame(10, $line->fresh()->requested_quantity);
        $this->assertSame(6, $line->fresh()->approved_quantity);
        $this->post(route('resident.documents.respond', $request), ['action' => 'accept'])->assertSessionHasErrors('request');
    }

    public function test_expired_offer_releases_hold_and_cannot_be_accepted(): void
    {
        $request = $this->application();
        $line = $this->reservation($request);
        $this->workflow->review($request, ['status' => 'awaiting_confirmation', 'review_note' => 'Only five chairs.', 'allocations' => [['id' => $line->id, 'approved_quantity' => 5]]], $this->staff);
        $this->travelTo($request->fresh()->confirmation_expires_at);
        $this->assertSame(100, app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at)['available']);
        $this->actingAs($request->resident, 'resident')->post(route('resident.documents.respond', $request), ['action' => 'accept'])->assertSessionHasErrors('request');
        $this->assertSame(RequestStatus::Expired, $request->fresh()->status);
        $this->assertSame(0, $this->workflow->expire());
        $this->get(route('resident.documents.edit', $request))->assertOk();
    }

    public function test_pending_competition_does_not_reserve_and_second_approval_cannot_overbook(): void
    {
        $resource = ResourceItem::factory()->create(['quantity' => 10]);
        $first = $this->application();
        $second = $this->application();
        $firstLine = $this->reservation($first, $resource, 8);
        $secondLine = $this->reservation($second, $resource, 8);
        $check = app(ResourceAvailability::class)->check($resource, $firstLine->starts_at, $firstLine->ends_at, $first->id);
        $this->assertSame(10, $check['available']);
        $this->assertCount(1, $check['pending']);
        $this->workflow->review($first, ['status' => 'approved', 'allocations' => [['id' => $firstLine->id, 'approved_quantity' => 8]]], $this->staff);
        $this->rejectedReview($second, ['status' => 'approved', 'allocations' => [['id' => $secondLine->id, 'approved_quantity' => 8]]], 'allocations');
        $this->assertSame(RequestStatus::Pending, $second->fresh()->status);
    }

    public function test_availability_uses_peak_overlap_and_allows_adjacent_bookings(): void
    {
        $resource = ResourceItem::factory()->create(['quantity' => 10]);
        $start = CarbonImmutable::now()->addDays(7);
        foreach ([0, 2] as $offset) {
            RequestReservation::factory()->create(['document_request_id' => $this->application('use_closure', RequestStatus::Approved)->id, 'resource_item_id' => $resource->id, 'approved_quantity' => 7, 'starts_at' => $start->addHours($offset), 'ends_at' => $start->addHours($offset + 2)]);
        }
        $this->assertSame(3, app(ResourceAvailability::class)->check($resource, $start, $start->addHours(4))['available']);
        $this->assertSame(10, app(ResourceAvailability::class)->check($resource, $start->addHours(4), $start->addHours(6))['available']);
    }

    public function test_overdue_equipment_blocks_future_stock_and_partial_returns_restore_only_returned_units(): void
    {
        $request = $this->application('use_closure', RequestStatus::Released);
        $resource = ResourceItem::factory()->create(['quantity' => 10]);
        $line = RequestReservation::factory()->create(['document_request_id' => $request->id, 'resource_item_id' => $resource->id, 'approved_quantity' => 8, 'issued_quantity' => 8, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
        $start = CarbonImmutable::now()->addWeek();
        $this->assertSame(2, app(ResourceAvailability::class)->check($resource, $start, $start->addDay())['available']);
        $this->workflow->equipment($request, $line->id, 3, true, $this->staff);
        $this->assertSame(5, $line->fresh()->outstanding());
        $this->assertTrue($line->fresh()->isOverdue());
        $this->assertSame(5, app(ResourceAvailability::class)->check($resource, $start, $start->addDay())['available']);
        $this->workflow->equipment($request, $line->id, 5, true, $this->staff);
        $this->assertSame(10, app(ResourceAvailability::class)->check($resource, $start, $start->addDay())['available']);
        $this->assertSame(2, $request->events()->count());
        $this->expectException(ValidationException::class);
        $this->workflow->equipment($request, $line->id, 1, true, $this->staff);
    }

    public function test_collection_is_limited_to_approved_quantity_and_return_cannot_exceed_collection(): void
    {
        $request = $this->application('use_closure', RequestStatus::Approved);
        $line = $this->reservation($request);
        $line->update(['approved_quantity' => 6, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay()]);
        $this->workflow->equipment($request, $line->id, 4, false, $this->staff);
        $this->workflow->equipment($request, $line->id, 2, false, $this->staff);
        $this->assertSame(6, $line->fresh()->issued_quantity);
        $this->expectException(ValidationException::class);
        $this->workflow->equipment($request, $line->id, 1, false, $this->staff);
    }

    public function test_resident_submission_and_revision_preserve_identity_history_and_private_attachments(): void
    {
        $resident = Resident::factory()->create();
        $type = DocumentType::factory()->create(['category' => 'clearance', 'is_active' => true]);
        $data = ['document_type_id' => $type->id, 'application_kind' => 'new', 'purpose' => 'House repair', 'files' => ['request_letter' => UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"), 'registration' => UploadedFile::fake()->image('dti.jpg')], 'status' => 'approved', 'resident_id' => 999];
        $this->actingAs($resident, 'resident')->post(route('resident.documents.store'), $data)->assertSessionHasNoErrors();
        $request = DocumentRequest::where('resident_id', $resident->id)->sole();
        $this->assertSame(RequestStatus::Pending, $request->status);
        $this->assertSame(2, $request->attachments()->count());
        $attachment = $request->attachments()->first();
        $this->get(route('resident.documents.attachment', $attachment))->assertOk();
        $this->workflow->review($request, ['status' => 'needs_revision', 'review_note' => 'Please clarify the location.'], $this->staff);
        $this->put(route('resident.documents.update', $request), ['document_type_id' => $type->id, 'application_kind' => 'new', 'purpose' => 'Repair at 12 Main Street'])->assertSessionHasNoErrors();
        $this->assertSame(RequestStatus::Pending, $request->fresh()->status);
        $this->assertSame(3, $request->events()->count());
        $this->assertSame(2, $request->attachments()->count());
        $this->put(route('resident.documents.update', $request), ['document_type_id' => $type->id, 'application_kind' => 'new', 'purpose' => 'Tamper'])->assertSessionHasErrors('request');
        $this->actingAs(Resident::factory()->create(), 'resident')->get(route('resident.documents.show', $request))->assertNotFound();
        $this->get(route('resident.documents.attachment', $attachment))->assertNotFound();
        $this->post(route('resident.documents.respond', $request), ['action' => 'cancel'])->assertNotFound();
    }

    public function test_submission_requires_category_specific_attachments_and_valid_resource_periods(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');
        $type = DocumentType::factory()->create(['category' => 'certification', 'is_active' => true]);
        $this->post(route('resident.documents.store'), ['document_type_id' => $type->id, 'purpose' => 'Employment'])->assertSessionHasErrors('files.photo_id');
        $type->update(['category' => 'use_closure']);
        $resource = ResourceItem::factory()->create(['kind' => 'street']);
        $data = ['document_type_id' => $type->id, 'purpose' => 'Community event', 'files' => ['request_letter' => UploadedFile::fake()->image('letter.png')], 'reservations' => [['resource_item_id' => $resource->id, 'requested_quantity' => 1, 'starts_at' => '2026-11-01T10:00', 'ends_at' => '2026-11-01T09:00']]];
        $this->post(route('resident.documents.store'), $data)->assertSessionHasErrors('reservations.0.ends_at');
        $data['reservations'][0]['ends_at'] = '2026-11-01T12:00';
        $this->post(route('resident.documents.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(CarbonImmutable::parse('2026-11-01T10:00:00+08:00')->setTimezone(config('app.timezone'))->format('Y-m-d H:i'), RequestReservation::sole()->starts_at->format('Y-m-d H:i'));
    }

    public function test_admin_review_and_inventory_pages_render_and_settings_are_admin_only(): void
    {
        $request = $this->application();
        $this->reservation($request);
        $this->actingAs($this->staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(EditDocumentRequest::class, ['record' => $request->id])->assertSuccessful()->assertSee('Availability and borrowing')->fillForm(['status' => 'approved'])->call('save')->assertHasNoFormErrors();
        $this->assertSame(RequestStatus::Approved, $request->fresh()->status);
        Livewire::test(ListDocumentRequests::class)->assertActionHidden('confirmationSettings');
        Livewire::test(ManageResourceItems::class)->assertSuccessful()->assertActionHidden('create');
        $this->staff->update(['role' => 'admin']);
        Livewire::test(ListDocumentRequests::class)->callAction('confirmationSettings', ['confirmation_hours' => 72])->assertHasNoActionErrors();
        $this->assertSame(72, WorkflowSetting::current()->confirmation_hours);
        Livewire::test(ManageResourceItems::class)->assertActionVisible('create');
    }

    public function test_new_form_and_revision_form_render_with_requirements(): void
    {
        $resident = Resident::factory()->create();
        $this->actingAs($resident, 'resident')->get(route('resident.documents.create'))->assertOk()->assertSee('RBI')->assertSee('Online payment is not available');
        $request = $this->application('clearance', RequestStatus::NeedsRevision);
        $this->actingAs($request->resident, 'resident')->get(route('resident.documents.edit', $request))->assertOk()->assertSee('Resubmit application');
    }

    public function test_printing_is_staff_only_and_blocked_until_review_and_payment_complete(): void
    {
        $request = $this->application('clearance');
        $this->actingAs($this->staff, 'employee')->get(route('admin.requests.print', $request))->assertForbidden();
        $this->workflow->review($request, ['status' => 'awaiting_payment'], $this->staff);
        $this->get(route('admin.requests.print', $request))->assertForbidden();
        $this->workflow->review($request, ['status' => 'processing', 'official_receipt' => 'PRINT-123', 'amount_paid' => 50], $this->staff);
        $this->get(route('admin.requests.print', $request))->assertOk()->assertSee('PRINT-123')->assertSee($request->resident->full_name);
        $this->assertSame(RequestStatus::Processing, $request->fresh()->status);
        auth('employee')->logout();
        $this->actingAs($request->resident, 'resident')->get(route('admin.requests.print', $request))->assertRedirect();
    }

    public function test_street_conflict_is_exclusive_and_unavailable_street_can_be_omitted_from_offer(): void
    {
        $street = ResourceItem::factory()->create(['kind' => 'street', 'quantity' => 1]);
        $first = $this->application();
        $firstLine = $this->reservation($first, $street, 1);
        $this->workflow->review($first, ['status' => 'approved', 'allocations' => [['id' => $firstLine->id, 'approved_quantity' => 1]]], $this->staff);
        $second = $this->application();
        $secondLine = $this->reservation($second, $street, 1);
        $equipment = $this->reservation($second);
        $this->rejectedReview($second, ['status' => 'approved', 'allocations' => [['id' => $secondLine->id, 'approved_quantity' => 1], ['id' => $equipment->id, 'approved_quantity' => 10]]], 'allocations');
        $this->workflow->review($second, ['status' => 'awaiting_confirmation', 'review_note' => 'Street unavailable. Equipment only.', 'allocations' => [['id' => $secondLine->id, 'approved_quantity' => 0], ['id' => $equipment->id, 'approved_quantity' => 10]]], $this->staff);
        $this->workflow->residentAction($second, 'revise');
        $this->assertSame(RequestStatus::NeedsRevision, $second->fresh()->status);
        $this->assertSame(100, app(ResourceAvailability::class)->check($equipment->resourceItem, $equipment->starts_at, $equipment->ends_at)['available']);
    }

    public function test_foreign_allocation_and_zero_offer_are_rejected_atomically(): void
    {
        $request = $this->application();
        $line = $this->reservation($request);
        $otherLine = $this->reservation($this->application());
        $this->rejectedReview($request, ['status' => 'approved', 'allocations' => [['id' => $otherLine->id, 'approved_quantity' => 1]]], 'allocations.0.id');
        $this->rejectedReview($request, ['status' => 'awaiting_confirmation', 'review_note' => 'Unavailable', 'allocations' => [['id' => $line->id, 'approved_quantity' => 0]]], 'allocations');
        $this->assertSame(RequestStatus::Pending, $request->fresh()->status);
        $this->assertSame(0, $request->events()->count());
    }

    public function test_confirmation_cannot_extend_past_resource_start_and_cancellation_releases_hold(): void
    {
        $request = $this->application();
        $line = $this->reservation($request);
        $line->update(['starts_at' => now()->addDay()]);
        $offer = ['status' => 'awaiting_confirmation', 'review_note' => 'Only five available.', 'allocations' => [['id' => $line->id, 'approved_quantity' => 5]]];
        $this->rejectedReview($request, $offer, 'allocations');
        $line->update(['starts_at' => now()->addDays(7)]);
        $this->workflow->review($request, $offer, $this->staff);
        $this->workflow->residentAction($request, 'cancel');
        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(100, app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at)['available']);
    }

    public function test_inventory_cannot_be_reduced_below_existing_commitments(): void
    {
        $resource = ResourceItem::factory()->create(['quantity' => 10]);
        $request = $this->application();
        $line = $this->reservation($request, $resource, 8);
        $this->workflow->review($request, ['status' => 'approved', 'allocations' => [['id' => $line->id, 'approved_quantity' => 8]]], $this->staff);
        $this->staff->update(['role' => 'admin']);
        $this->actingAs($this->staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageResourceItems::class)->callTableAction('edit', $resource, ['quantity' => 7])->assertHasTableActionErrors(['quantity']);
        $this->assertSame(10, $resource->fresh()->quantity);
        Livewire::test(ManageResourceItems::class)->callTableAction('edit', $resource, ['quantity' => 12])->assertHasNoTableActionErrors();
        $this->assertSame(12, $resource->fresh()->quantity);
    }

    public function test_staff_can_cancel_a_reservation_only_after_outstanding_equipment_is_returned(): void
    {
        $request = $this->application('use_closure', RequestStatus::Released);
        $line = $this->reservation($request);
        $line->update(['approved_quantity' => 10, 'issued_quantity' => 4]);
        $this->rejectedReview($request, ['status' => 'cancelled', 'review_note' => 'Event cancelled.'], 'status');
        $this->workflow->equipment($request, $line->id, 4, true, $this->staff);
        $this->workflow->review($request, ['status' => 'cancelled', 'review_note' => 'Event cancelled. All equipment returned.'], $this->staff);
        $this->assertSame(RequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(100, app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at)['available']);
    }
}
