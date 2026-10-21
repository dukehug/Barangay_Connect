@extends('layouts.resident')
@section('title', 'DOC-'.$document->id)
@section('main-class', 'documents-main')
@section('content')
    <section class="greeting page-head"><div><h1>DOC-{{ $document->id }}</h1><p>{{ $document->documentType->document_type_name }}</p></div><a href="{{ route('resident.documents') }}">Back to My Documents</a></section>
    <section class="workflow-card">
        <div class="document-state" data-status="{{ $document->status->value }}">@include('resident.status', ['status' => $document->status])</div>
        <h2>Application details</h2>
        <p class="portal-prose">{{ $document->purpose }}</p>
        @if($document->application_kind)<p>Application: {{ ucfirst($document->application_kind) }}</p>@endif
        @if($document->review_note)<div class="workflow-notice"><strong>Message from the office</strong><p class="portal-prose">{{ $document->review_note }}</p></div>@endif
        @if($document->status === \App\Enums\RequestStatus::AwaitingPayment)
            <p class="workflow-notice">Your application has passed review. Please pay at the Barangay Office and collect your Official Receipt. Listed fee: ₱{{ $document->documentType->document_type_fee }}. Payment is accepted in person only.</p>
        @endif
        @if($document->paid_at)<p>Office payment recorded: ₱{{ $document->amount_paid }} · OR {{ $document->official_receipt }}</p>@endif
        @if($document->status === \App\Enums\RequestStatus::Ready)<p class="workflow-notice">Your document is ready for pickup at the Barangay Office.</p>@endif
        @if($document->status === \App\Enums\RequestStatus::AwaitingConfirmation)
            <div class="workflow-notice"><strong>Please review the arrangement below.</strong><p>Accept by {{ $document->confirmation_expires_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }} Philippine time. Saturday and Sunday do not count toward your confirmation period. Resources are held until this deadline.</p></div>
        @endif
        @if($document->status === \App\Enums\RequestStatus::Expired)<p class="workflow-notice">The confirmation deadline passed and the reserved resources were released. Revise and resubmit this application if you still need them.</p>@endif
    </section>
    @if($document->reservations->isNotEmpty())
        <section class="workflow-card"><h2>Requested and offered arrangement</h2><p>Times shown in Philippine time. Document release and equipment return are tracked separately.</p>
            <div class="document-table-scroll"><table class="document-table"><thead><tr><th>Street / equipment</th><th>Period</th><th>Requested</th><th>Offered / approved</th><th>Collected</th><th>Returned</th><th>Outstanding</th></tr></thead><tbody>
            @foreach($document->reservations as $line)
                <tr><td>{{ $line->resourceItem->name }}</td><td>{{ $line->starts_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}<br>to {{ $line->ends_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}</td><td>{{ $line->requested_quantity }}</td><td>{{ $line->approved_quantity }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->issued_quantity : '—' }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->returned_quantity : '—' }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->outstanding() : '—' }} @if($line->isOverdue())<strong>Overdue</strong>@endif</td></tr>
            @endforeach
            </tbody></table></div>
        </section>
    @endif
    <section class="workflow-card"><h2>Next action</h2><div class="workflow-actions">
        @if($document->status === \App\Enums\RequestStatus::AwaitingConfirmation)
            <form method="POST" action="{{ route('resident.documents.respond', $document) }}">@csrf<input type="hidden" name="action" value="accept"><button class="request-new" type="submit">Accept arrangement</button></form>
            <form method="POST" action="{{ route('resident.documents.respond', $document) }}">@csrf<input type="hidden" name="action" value="revise"><button type="submit">Request changes</button></form>
        @endif
        @if($document->isEditable())<a class="request-new" href="{{ route('resident.documents.edit', $document) }}">Revise and resubmit</a>@endif
        @if(in_array($document->status, [\App\Enums\RequestStatus::Pending, \App\Enums\RequestStatus::NeedsRevision, \App\Enums\RequestStatus::Expired, \App\Enums\RequestStatus::AwaitingConfirmation, \App\Enums\RequestStatus::AwaitingPayment], true))
            <form method="POST" action="{{ route('resident.documents.respond', $document) }}" data-confirm-cancel>@csrf<input type="hidden" name="action" value="cancel"><button type="submit">Cancel request</button></form>
        @else <p>For any changes, contact the Barangay Office and quote DOC-{{ $document->id }}.</p>@endif
    </div></section>
    @include('resident.attachment-gallery')
    <details class="workflow-card workflow-disclosure"><summary>Application history</summary><ol class="workflow-history">@forelse($document->events as $event)<li><strong>{{ $event->action }}</strong><span>{{ $event->created_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}</span>@if($event->note)<p class="portal-prose">{{ $event->note }}</p>@endif</li>@empty<li>Existing application. New updates will appear here.</li>@endforelse</ol></details>
@endsection
@push('scripts')<script src="{{ asset('resident/js/documents.js') }}?v={{ filemtime(public_path('resident/js/documents.js')) }}" defer></script>@endpush
