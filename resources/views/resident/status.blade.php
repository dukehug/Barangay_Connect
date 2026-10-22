<span class="document-status status-{{ match ($status->value) {
    'ready' => 'primary',
    'cancelled' => 'secondary',
    'approved', 'released', 'resolved' => 'success',
    'rejected' => 'danger',
    'pending', 'needs_revision', 'awaiting_payment' => 'warning',
    'processing', 'in_progress', 'awaiting_signature' => 'info',
    'awaiting_confirmation' => 'light',
    'expired' => 'dark',
    default => 'secondary',
} }}">{{ $status->getLabel() }}</span>
