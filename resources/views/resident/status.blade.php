<span @class([
    'document-status',
    'status-completed' => in_array($status->value, [
        'released',
        'resolved',
        'ready',
    ]),
    'status-denied' => $status->value === 'rejected',
    'status-processing' => in_array($status->value, [
        'pending',
        'processing',
        'in_progress',
    ]),
])>{{ $status->getLabel() }}</span>
