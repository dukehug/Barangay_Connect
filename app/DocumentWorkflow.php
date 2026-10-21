<?php

namespace App;

use App\Enums\RequestStatus;
use App\Models\BarangayEmployee;
use App\Models\DocumentRequest;
use App\Models\RequestReservation;
use App\Models\WorkflowSetting;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentWorkflow
{
    /** @return array<string, string> */
    public function transitions(DocumentRequest $request): array
    {
        $next = match ($request->status) {
            RequestStatus::Pending => [RequestStatus::NeedsRevision, RequestStatus::Rejected, match ($request->workflowCategory()) {
                'clearance' => RequestStatus::AwaitingPayment,
                'use_closure' => RequestStatus::Approved,
                default => RequestStatus::Processing,
            }, ...($request->workflowCategory() === 'use_closure' ? [RequestStatus::AwaitingConfirmation] : [])],
            RequestStatus::AwaitingPayment => [RequestStatus::Processing],
            RequestStatus::Processing => [$request->workflowCategory() === 'certification' ? RequestStatus::AwaitingSignature : RequestStatus::Ready],
            RequestStatus::AwaitingSignature, RequestStatus::Approved => [RequestStatus::Ready],
            RequestStatus::Ready => [RequestStatus::Released],
            default => [],
        };

        if ($request->workflowCategory() === 'use_closure' && in_array($request->status, [RequestStatus::AwaitingConfirmation, RequestStatus::Approved, RequestStatus::Ready, RequestStatus::Released], true)) {
            $next[] = RequestStatus::Cancelled;
        }

        $options = collect([$request->status, ...$next])->mapWithKeys(fn (RequestStatus $status): array => [$status->value => $status->getLabel()])->all();
        foreach ($next as $status) {
            $options[$status->value] = match ($status) {
                RequestStatus::Processing => $request->workflowCategory() === 'certification' ? 'Verify RBI and queue for printing' : 'Record office payment and queue for printing',
                RequestStatus::AwaitingConfirmation => 'Send arrangement for resident confirmation',
                RequestStatus::AwaitingSignature => 'Printed — obtain officer signature',
                RequestStatus::Ready => $request->workflowCategory() === 'certification' ? 'Signature obtained — ready for pickup' : 'Printed — ready for pickup',
                RequestStatus::Released => 'Record document released to applicant',
                default => $status->getLabel(),
            };
        }

        return $options;
    }

    /**
     * Serialize reservations, acceptance, inventory changes and borrowing under the same database lock.
     */
    public function locked(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            WorkflowSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();

            return $operation();
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function review(DocumentRequest $request, array $data, BarangayEmployee $staff): DocumentRequest
    {
        abort_unless($staff->is_active, 403);

        return $this->locked(function () use ($request, $data, $staff): DocumentRequest {
            $request = DocumentRequest::query()->lockForUpdate()->findOrFail($request->id);
            $validated = Validator::make($data, ['status' => ['required', Rule::in(array_keys($this->transitions($request)))], 'review_note' => ['nullable', 'string', 'max:5000']])->validate();
            $next = RequestStatus::from($validated['status']);
            if ($next === $request->status) {
                return $request;
            }
            $note = trim($validated['review_note'] ?? '');
            if (in_array($next, [RequestStatus::NeedsRevision, RequestStatus::Rejected, RequestStatus::AwaitingConfirmation, RequestStatus::Cancelled], true) && $note === '') {
                $this->invalid('review_note', 'Explain the corrections, rejection, cancellation, or unavailable items to the resident.');
            }
            if ($next === RequestStatus::Cancelled) {
                if ($request->reservations()->whereColumn('issued_quantity', '>', 'returned_quantity')->exists()) {
                    $this->invalid('status', 'Record all outstanding equipment returns before cancelling this reservation.');
                }
                $request->confirmation_expires_at = null;
            }
            if ($request->status === RequestStatus::Pending && $next === RequestStatus::Processing && $request->workflowCategory() === 'certification') {
                Validator::make($data, ['rbi_verified' => ['accepted']])->validate();
                $request->rbi_verified_at = now();
            }
            if ($request->status === RequestStatus::AwaitingPayment) {
                $payment = Validator::make($data, [
                    'official_receipt' => ['required', 'string', 'max:100', Rule::unique('document_requests', 'official_receipt')->ignore($request->id)],
                    'amount_paid' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
                ])->validate();
                $request->fill($payment);
                $request->paid_at = now();
            }
            if (in_array($next, [RequestStatus::Approved, RequestStatus::AwaitingConfirmation], true)) {
                $this->reserve($request, $data['allocations'] ?? [], $next);
            }
            $request->fill(['status' => $next, 'review_note' => $note, 'staff_id' => $request->staff_id ?? $staff->id])->save();
            $this->record($request, $next->getLabel(), $note, $staff->id, [
                'allocations' => $request->reservations()->get()->toArray(),
                'official_receipt' => $request->official_receipt, 'amount_paid' => $request->amount_paid,
                'confirmation_expires_at' => $request->confirmation_expires_at?->toIso8601String(),
            ]);

            return $request;
        });
    }

    /** @param array<int, array{id: int, approved_quantity: int}> $allocations */
    private function reserve(DocumentRequest $request, array $allocations, RequestStatus $next): void
    {
        $lines = $request->reservations()->with('resourceItem')->get();
        Validator::make(['allocations' => $allocations], [
            'allocations' => ['required', 'array', 'size:'.$lines->count()],
            'allocations.*.id' => ['required', 'integer', 'distinct', Rule::in($lines->modelKeys())],
            'allocations.*.approved_quantity' => ['required', 'integer', 'min:0'],
        ])->validate();
        $proposals = collect($allocations)->keyBy('id');
        $partial = false;
        $total = 0;
        foreach ($lines as $line) {
            $quantity = (int) $proposals[$line->id]['approved_quantity'];
            if ($quantity > $line->requested_quantity) {
                $this->invalid('allocations', 'An allocation cannot exceed the requested quantity.');
            }
            if ($quantity > 0) {
                if ($line->starts_at->isPast()) {
                    $this->invalid('allocations', 'The requested time has passed. Ask the resident to revise the dates.');
                }
                $available = app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at, $request->id);
                if ($quantity > $available['available']) {
                    $this->invalid('allocations', $line->resourceItem->name.': only '.$available['available'].' available. '.implode('; ', $available['conflicts']));
                }
            }
            $partial = $partial || $quantity < $line->requested_quantity;
            $total += $quantity;
            $line->update(['approved_quantity' => $quantity]);
        }
        if ($total === 0) {
            $this->invalid('allocations', 'Approve at least one item, or reject / return the request for revision.');
        }
        if ($partial && $next !== RequestStatus::AwaitingConfirmation) {
            $this->invalid('status', 'Partial arrangements must be sent to the resident for confirmation.');
        }
        if ($next === RequestStatus::AwaitingConfirmation) {
            $deadline = app(ConfirmationDeadline::class)->calculate(CarbonImmutable::now(), WorkflowSetting::current()->confirmation_hours);
            $firstUse = $lines->filter(fn (RequestReservation $line): bool => $line->approved_quantity > 0)->min('starts_at');
            if ($deadline > $firstUse) {
                $this->invalid('allocations', 'The confirmation deadline would be after the first use. Ask the resident for later dates, or adjust the confirmation hours before sending.');
            }
            $request->confirmation_expires_at = $deadline;
        }
    }

    public function residentAction(DocumentRequest $request, string $action): void
    {
        $this->expire();
        $this->locked(function () use ($request, $action): void {
            $request = DocumentRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($action === 'accept') {
                if ($request->status !== RequestStatus::AwaitingConfirmation || ! $request->confirmation_expires_at?->isFuture()) {
                    $this->invalid('request', 'This arrangement is no longer available for confirmation.');
                }
                foreach ($request->reservations()->with('resourceItem')->where('approved_quantity', '>', 0)->get() as $line) {
                    $available = app(ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at, $request->id);
                    if ($available['available'] < $line->approved_quantity || $line->starts_at->isPast()) {
                        $this->invalid('request', 'Availability has changed. Please contact the office or request a revision.');
                    }
                }
                $request->fill(['status' => RequestStatus::Approved, 'accepted_at' => now()])->save();
                $this->record($request, 'Resident accepted arrangement');

                return;
            }
            $allowed = [RequestStatus::Pending, RequestStatus::NeedsRevision, RequestStatus::Expired, RequestStatus::AwaitingConfirmation, RequestStatus::AwaitingPayment];
            if (! in_array($request->status, $allowed, true)) {
                $this->invalid('request', 'Contact the office to change an approved or completed request.');
            }
            if ($action === 'revise' && $request->status !== RequestStatus::AwaitingConfirmation) {
                $this->invalid('request', 'Only an offered arrangement can be declined for revision.');
            }
            $request->update(['status' => $action === 'revise' ? RequestStatus::NeedsRevision : RequestStatus::Cancelled, 'confirmation_expires_at' => null]);
            $this->record($request, $action === 'revise' ? 'Resident requested revision' : 'Resident cancelled request');
        });
    }

    public function expire(): int
    {
        return $this->locked(function (): int {
            $requests = DocumentRequest::where('status', RequestStatus::AwaitingConfirmation)->where('confirmation_expires_at', '<=', now())->get();
            foreach ($requests as $request) {
                $request->update(['status' => RequestStatus::Expired]);
                $this->record($request, 'Confirmation expired', 'Reserved resources have been released. Revise and resubmit this request if still needed.');
            }

            return $requests->count();
        });
    }

    public function equipment(DocumentRequest $request, int $lineId, int $quantity, bool $returning, BarangayEmployee $staff): void
    {
        abort_unless($staff->is_active, 403);
        $this->locked(function () use ($request, $lineId, $quantity, $returning, $staff): void {
            $request->refresh();
            $line = $request->reservations()->with('resourceItem')->findOrFail($lineId);
            if ($quantity < 1 || $line->resourceItem->kind !== 'equipment') {
                $this->invalid('quantity', 'Choose equipment and a positive whole number.');
            }
            if ($returning) {
                if ($quantity > $line->outstanding()) {
                    $this->invalid('quantity', 'The return exceeds the quantity still borrowed.');
                }
                $line->increment('returned_quantity', $quantity);
            } else {
                if (! in_array($request->status, [RequestStatus::Approved, RequestStatus::Ready, RequestStatus::Released], true)
                    || $line->issued_quantity + $quantity > $line->approved_quantity || $line->ends_at->isPast()) {
                    $this->invalid('quantity', 'Only approved, unissued quantities within the borrowing period can be issued.');
                }
                if ($line->starts_at->isFuture()) {
                    $this->invalid('quantity', 'Equipment cannot be collected before the reserved pickup time.');
                }
                $availability = app(ResourceAvailability::class)->check($line->resourceItem, CarbonImmutable::now(), $line->ends_at, $request->id);
                if ($availability['available'] < $line->outstanding() + $quantity) {
                    $this->invalid('quantity', 'Equipment is unavailable, possibly because of overdue returns. Review the conflicts.');
                }
                $line->increment('issued_quantity', $quantity);
            }
            $this->record($request, $returning ? 'Equipment returned' : 'Equipment collected', $line->resourceItem->name.': '.$quantity, $staff->id, ['reservation_id' => $line->id, 'quantity' => $quantity, 'outstanding' => $line->outstanding()]);
        });
    }

    /** @param array<string, mixed> $details */
    public function record(DocumentRequest $request, string $action, ?string $note = null, ?int $staffId = null, array $details = []): void
    {
        $request->events()->create(['action' => $action, 'note' => $note, 'staff_id' => $staffId, 'details' => $details]);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
