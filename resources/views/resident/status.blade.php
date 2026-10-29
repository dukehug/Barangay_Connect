@php
    $statusTone = match ($status->value) {
        'ready', 'awaiting_confirmation' => 'primary',
        'approved', 'released', 'resolved' => 'success',
        'rejected' => 'danger',
        'pending', 'needs_revision', 'awaiting_payment' => 'warning',
        'processing', 'in_progress', 'awaiting_signature' => 'info',
        default => 'secondary',
    };
@endphp
<span class="document-status status-{{ $statusTone }}">
    <svg class="document-status-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        @switch($statusTone)
            @case('warning')
                <path d="M10.3 3.9 2.1 18a2 2 0 0 0 1.7 3h16.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                <path d="M12 9v4m0 4h.01" />
                @break
            @case('info')
                <circle cx="12" cy="12" r="9" stroke-dasharray="3 4" />
                @break
            @case('primary')
                <path d="m22 2-7 20-4-9-9-4Z M22 2 11 13" />
                @break
            @case('success')
                <circle cx="12" cy="12" r="9" />
                <path d="m8 12 3 3 5-6" />
                @break
            @case('danger')
                <circle cx="12" cy="12" r="9" />
                <path d="m9 9 6 6m0-6-6 6" />
                @break
            @default
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7v5l-3 2" />
        @endswitch
    </svg>
    <span>{{ $status->getLabel() }}</span>
</span>
