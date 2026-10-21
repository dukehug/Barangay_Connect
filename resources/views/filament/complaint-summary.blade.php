@php
    $complaint = $getRecord()->fresh()->load('staff');
    $history = \App\Models\AuditLog::query()
        ->where('table_affected', $complaint->getTable())
        ->where('record_id', $complaint->id)
        ->with('staff')
        ->orderByDesc('id')
        ->get();
@endphp
<div style="display: grid; gap: 1.5rem; line-height: 1.6; overflow-wrap: anywhere;">
    <section>
        <h3><strong>Office record</strong></h3>
        <p>{{ $complaint->reference }} · {{ $complaint->status->getLabel() }}</p>
        <p>Assigned staff: {{ $complaint->staff?->getFilamentName() ?? 'Unassigned' }}</p>
        <p>Submitted: {{ $complaint->created_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</p>
        <p>Last updated: {{ $complaint->updated_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</p>
    </section>
    <section>
        <h3><strong>History</strong></h3>
        @forelse($history as $event)
            <div style="margin-top: .75rem; padding: .75rem 1rem; border: 1px solid #94a3b8; border-radius: .5rem;">
                {{ $event->created_at->setTimezone('Asia/Manila')->format('M d Y H:i') }} · {{ ucfirst($event->action) }} · {{ $event->staff?->getFilamentName() ?? 'Former employee' }}
            </div>
        @empty
            <p>No history recorded yet.</p>
        @endforelse
    </section>
</div>
