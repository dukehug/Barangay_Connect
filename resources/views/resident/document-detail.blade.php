@extends('layouts.resident')
@section('title', $document->reference)
@section('main-class', 'documents-main document-detail-page')
@section('content')

    <section class="greeting page-head">
        <div>
            <h1>{{ $document->reference }}</h1>
            <p>{{ $document->documentType->document_type_name }}</p>
        </div>
        <a class="request-new" href="{{ route('resident.documents') }}">Back to My Documents</a>
    </section>


    @php
        $isStopped = in_array($document->status, [\App\Enums\RequestStatus::Rejected, \App\Enums\RequestStatus::Cancelled], true);
        $needsAttention = in_array($document->status, [\App\Enums\RequestStatus::NeedsRevision, \App\Enums\RequestStatus::Expired], true);
        $stages = $isStopped
            ? ['Submitted', $document->status->getLabel()]
            : ['Submitted', 'Review', 'Preparation', 'Ready for pickup', 'Released'];
        $currentStage = match ($document->status) {
            \App\Enums\RequestStatus::Approved, \App\Enums\RequestStatus::Processing, \App\Enums\RequestStatus::AwaitingSignature => 2,
            \App\Enums\RequestStatus::Ready => 3,
            \App\Enums\RequestStatus::Released => 4,
            default => 1,
        };
    @endphp

    <section class="workflow-card document-state document-progress" data-status="{{ $document->status->value }}" aria-labelledby="document-progress-title">
        <header class="document-progress-header">
            <div>
                <h2 id="document-progress-title">Application progress</h2>
                <p>{{ $isStopped ? 'This application has ended. See the history below for details.' : 'Follow the main stages of your document request.' }}</p>
            </div>
            @include('resident.status', ['status' => $document->status])
        </header>
        <div class="document-stepper-scroll" role="region" aria-label="Application stages" tabindex="0">
            <ol class="document-stepper">
                @foreach($stages as $stage)
                    <li @class([
                        'document-step',
                        'is-complete' => $loop->index < $currentStage || $document->status === \App\Enums\RequestStatus::Released,
                        'is-current' => $loop->index === $currentStage,
                        'is-stopped' => $isStopped && $loop->index === $currentStage,
                        'needs-attention' => $needsAttention && $loop->index === $currentStage,
                    ]) @if($loop->index === $currentStage) aria-current="step" @endif>
                        <span class="document-step-marker" aria-hidden="true">
                            @if($loop->index < $currentStage || $document->status === \App\Enums\RequestStatus::Released)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6" /></svg>
                            @elseif(($isStopped || $needsAttention) && $loop->index === $currentStage)
                                {{ $isStopped ? '×' : '!' }}
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>
                        <strong>{{ $stage }}</strong>
                        <span class="document-step-caption">{{ $loop->index === $currentStage ? $document->status->getLabel() : ($loop->index < $currentStage ? 'Completed' : 'Upcoming') }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <details class="workflow-card workflow-disclosure document-history" open>
        <summary>Application history <span>{{ $document->events->count() }} updates</span></summary>
        <br>
        <ol class="workflow-history document-timeline">
            @forelse($document->events as $event)
                <li @class(['document-timeline-event', 'is-latest' => $loop->first])>
                    <div class="document-timeline-heading">
                        <h3>{{ $event->action }}</h3>
                        <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->setTimezone('Asia/Manila')->format('M d, Y · H:i') }}</time>
                    </div>
                    @if($event->note)<p class="portal-prose">{{ $event->note }}</p>@endif
                </li>
            @empty
                <li class="document-timeline-event"><h3>Existing application</h3><p>New updates will appear here.</p></li>
            @endforelse
        </ol>
    </details>

    <section class="workflow-card">
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

        <section class="workflow-card"><h2>Requested and offered arrangement</h2>
            <div class="document-table-scroll"><table class="document-table"><thead><tr><th>Street / equipment</th><th>Period</th><th>Requested</th><th>Offered / approved</th><th>Collected</th><th>Returned</th><th>Outstanding</th></tr></thead><tbody>
            @foreach($document->reservations as $line)
                <tr><td>{{ $line->resourceItem->name }}</td><td>{{ $line->starts_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}<br>to {{ $line->ends_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}</td><td>{{ $line->requested_quantity }}</td><td>{{ $line->approved_quantity }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->issued_quantity : '—' }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->returned_quantity : '—' }}</td><td>{{ $line->resourceItem->kind === 'equipment' ? $line->outstanding() : '—' }} @if($line->isOverdue())<strong>Overdue</strong>@endif</td></tr>
            @endforeach
            </tbody></table></div>
        </section>

    @endif

    <section class="workflow-card"><h2>Next action</h2>
        <div class="workflow-actions">
            @if($document->status === \App\Enums\RequestStatus::AwaitingConfirmation)
                <form method="POST" action="{{ route('resident.documents.respond', $document) }}">@csrf<input type="hidden" name="action" value="accept"><button class="request-new" type="submit">Accept arrangement</button></form>
                <form method="POST" action="{{ route('resident.documents.respond', $document) }}">@csrf<input type="hidden" name="action" value="revise"><button type="submit">Request changes</button></form>
            @endif
            @if($document->isEditable())<a class="request-new" href="{{ route('resident.documents.edit', $document) }}">Revise and resubmit</a>@endif
            @if(in_array($document->status, [\App\Enums\RequestStatus::Pending, \App\Enums\RequestStatus::NeedsRevision, \App\Enums\RequestStatus::Expired, \App\Enums\RequestStatus::AwaitingConfirmation, \App\Enums\RequestStatus::AwaitingPayment], true))
                <form method="POST" action="{{ route('resident.documents.respond', $document) }}" data-confirm-cancel>@csrf<input type="hidden" name="action" value="cancel"><button type="submit">Cancel request</button></form>
            @else <p>For any changes, contact the Barangay Office and quote {{ $document->reference }}.</p>@endif
        </div>
    </section>

    @include('resident.attachment-gallery')
@endsection
@push('scripts')<script src="{{ asset('resident/js/documents.js') }}?v={{ filemtime(public_path('resident/js/documents.js')) }}" defer></script>@endpush
