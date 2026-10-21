<?php

namespace App;

use App\Enums\RequestStatus;
use App\Models\RequestReservation;
use App\Models\ResourceItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ResourceAvailability
{
    /** @return array{capacity: int, reserved: int, available: int, conflicts: array<int, string>, pending: array<int, string>} */
    public function check(ResourceItem $resource, CarbonImmutable $start, CarbonImmutable $end, ?int $excludeRequest = null): array
    {
        $reservations = $resource->reservations()->with('documentRequest')
            ->when($excludeRequest, fn (Builder $query): Builder => $query->where('document_request_id', '!=', $excludeRequest))
            ->where(function (Builder $query) use ($start, $end): void {
                $query->where(fn (Builder $overlap): Builder => $overlap->where('starts_at', '<', $end)->where('ends_at', '>', $start))
                    ->orWhere(fn (Builder $overdue): Builder => $overdue->where('ends_at', '<', now())->whereColumn('issued_quantity', '>', 'returned_quantity'));
            })->get();
        $intervals = [];
        $pending = [];
        $conflicts = [];
        foreach ($reservations as $line) {
            $request = $line->documentRequest;
            $overlaps = $line->starts_at < $end && $line->ends_at > $start;
            $reference = $request->reference;
            if ($request->status === RequestStatus::Pending && $overlaps) {
                $pending[] = $reference.' requests '.$line->requested_quantity.' ('.$this->period($line).')';
            }
            $reserved = in_array($request->status, [RequestStatus::Approved, RequestStatus::Processing, RequestStatus::AwaitingSignature, RequestStatus::Ready, RequestStatus::Released], true)
                || ($request->status === RequestStatus::AwaitingConfirmation && $request->confirmation_expires_at?->isFuture());
            if (! $reserved) {
                continue;
            }
            $quantity = max(0, $line->approved_quantity - $line->returned_quantity);
            if ($overlaps && $quantity > 0) {
                $intervals[] = [max($start, $line->starts_at), min($end, $line->ends_at), $quantity];
                $conflicts[] = $reference.' reserves '.$quantity.' ('.$this->period($line).')';
            }
            if ($line->isOverdue()) {
                $intervals[] = [max($start, $line->ends_at), $end, $line->outstanding()];
                $conflicts[] = $reference.' overdue: '.$line->outstanding().' not returned';
            }
        }
        $peak = 0;
        foreach ([$start, ...array_column($intervals, 0)] as $point) {
            $used = 0;
            foreach ($intervals as [$from, $until, $quantity]) {
                if ($point >= $from && $point < $until) {
                    $used += $quantity;
                }
            }
            $peak = max($peak, $used);
        }
        $capacity = $resource->kind === 'street' ? 1 : $resource->quantity;

        return ['capacity' => $capacity, 'reserved' => $peak, 'available' => $resource->is_active ? max(0, $capacity - $peak) : 0, 'conflicts' => array_values(array_unique($conflicts)), 'pending' => $pending];
    }

    private function period(RequestReservation $line): string
    {
        return $line->starts_at->setTimezone('Asia/Manila')->format('M d H:i').' – '.$line->ends_at->setTimezone('Asia/Manila')->format('M d H:i');
    }
}
