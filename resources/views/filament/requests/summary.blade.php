@php
    $document = $getRecord()->fresh()->load('attachments', 'reservations.resourceItem', 'events');
@endphp
<div style="display: grid; gap: 1.25rem;">
    <section>
        <h3><strong>Application / supporting documents</strong></h3>
        <p>DOC-{{ $document->id }} · {{ $document->status->getLabel() }} @if($document->application_kind) · {{ ucfirst($document->application_kind) }} application @endif</p>
        @if($document->workflowCategory() === 'clearance')<p>Review → office payment and OR → print → pickup → release.</p>@endif
        @if($document->workflowCategory() === 'certification')<p>Manually verify RBI registration (8 months) → print → officer signature → pickup → release.</p>@endif
        @foreach($document->attachments as $attachment)
            <p><a style="text-decoration: underline" href="{{ route('admin.requests.attachment', $attachment) }}">{{ $attachment->name }}</a> · {{ str_replace('_', ' ', $attachment->kind) }} · {{ $attachment->created_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</p>
        @endforeach
        @if($document->attachments->isEmpty())<p>No uploaded attachments. For older applications, verify the office's paper records before proceeding.</p>@endif
        @if($document->rbi_verified_at)<p>RBI eligibility verified: {{ $document->rbi_verified_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</p>@endif
        @if($document->official_receipt)<p>OR: {{ $document->official_receipt }} · ₱{{ $document->amount_paid }}</p>@endif
        @if($document->confirmation_expires_at)<p>Confirmation deadline: {{ $document->confirmation_expires_at->setTimezone('Asia/Manila')->format('M d Y H:i') }} Philippine time.</p>@endif
    </section>
    @if($document->reservations->isNotEmpty())
        <section><h3><strong>Availability and borrowing</strong></h3><p>Availability excludes this application. Pending applications are warnings only. Check the same listed road segment; neighboring or overlapping segments need manual review.</p>
        @foreach($document->reservations as $line)
            @php($availability = app(\App\ResourceAvailability::class)->check($line->resourceItem, $line->starts_at, $line->ends_at, $document->id))
            <div style="padding: 1rem; margin-top: .75rem; border: 1px solid #94a3b8; border-radius: .5rem;">
                <strong>{{ $line->resourceItem->name }}</strong>
                <p>{{ $line->starts_at->setTimezone('Asia/Manila')->format('M d Y H:i') }} – {{ $line->ends_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</p>
                <p>Requested: {{ $line->requested_quantity }} · Allocated: {{ $line->approved_quantity }} · Total: {{ $availability['capacity'] }} · Reserved by others (peak): {{ $availability['reserved'] }} · Available: {{ $availability['available'] }}</p>
                @if($line->resourceItem->kind === 'equipment')<p>Collected: {{ $line->issued_quantity }} · Returned: {{ $line->returned_quantity }} · Outstanding: {{ $line->outstanding() }} @if($line->isOverdue())<strong> — OVERDUE</strong>@endif</p>@endif
                @foreach($availability['conflicts'] as $conflict)<p>Reservation / overdue: {{ $conflict }}</p>@endforeach
                @foreach($availability['pending'] as $pending)<p>Pending competition: {{ $pending }}</p>@endforeach
            </div>
        @endforeach
        </section>
    @endif
    <section><h3><strong>History</strong></h3>
        @foreach($document->events as $event)
            <details style="margin-top: .5rem;"><summary>{{ $event->created_at->setTimezone('Asia/Manila')->format('M d Y H:i') }} · {{ $event->action }} @if($event->staff_id) · Staff #{{ $event->staff_id }} @endif</summary>
                <p style="white-space: pre-wrap">{{ $event->note }}</p>
                @foreach(($event->details['allocations'] ?? $event->details['reservations'] ?? []) as $allocation)
                    <p>Resource #{{ $allocation['resource_item_id'] }}: requested {{ $allocation['requested_quantity'] }}, allocated {{ $allocation['approved_quantity'] }} · {{ $allocation['starts_at'] }} – {{ $allocation['ends_at'] }}</p>
                @endforeach
            </details>
        @endforeach
    </section>
</div>
