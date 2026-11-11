@extends('layouts.resident')
@section('title', $complaint->reference)
@section('main-class', 'documents-main complaint-detail-page')
@section('content')

    <section class="greeting page-head">
            <div>
                <h1>{{ $complaint->reference }}</h1>
                <p>{{ $complaint->complaint_type }}</p>
            </div>
            <a class="request-new" href="{{ route('resident.complaints') }}" >Back to My Complaints</a>
    </section>

    @php
        $isStopped = $complaint->status === \App\Enums\ComplaintStatus::Rejected;
        $isComplete = $complaint->status === \App\Enums\ComplaintStatus::Resolved;
        $stages = $isStopped ? ['Submitted', 'Rejected'] : ['Submitted', 'Review', 'In progress', 'Resolved'];
        $currentStage = match ($complaint->status) {
            \App\Enums\ComplaintStatus::InProgress => 2,
            \App\Enums\ComplaintStatus::Resolved => 3,
            default => 1,
        };
    @endphp

    <section class="workflow-card document-state document-progress" data-status="{{ $complaint->status->value }}" aria-labelledby="complaint-progress-title">
        <header class="document-progress-header">
            <div>
                <h2 id="complaint-progress-title">Complaint progress</h2>
                <p>{{ $isStopped ? 'This complaint has ended. See the details below for the office response.' : 'Follow the main stages of your complaint.' }}</p>
            </div>
            @include('resident.status', ['status' => $complaint->status])
        </header>
        <div class="document-stepper-scroll" role="region" aria-label="Complaint stages" tabindex="0">
            <ol class="document-stepper">
                @foreach($stages as $stage)
                    <li @class([
                        'document-step',
                        'is-complete' => $loop->index < $currentStage || $isComplete,
                        'is-current' => $loop->index === $currentStage,
                        'is-stopped' => $isStopped && $loop->index === $currentStage,
                    ]) @if($loop->index === $currentStage) aria-current="step" @endif>
                        <span class="document-step-marker" aria-hidden="true">
                            @if($loop->index < $currentStage || $isComplete)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6" /></svg>
                            @elseif($isStopped && $loop->index === $currentStage)
                                ×
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>
                        <strong>{{ $stage }}</strong>
                        <span class="document-step-caption">{{ $loop->index === $currentStage ? $complaint->status->getLabel() : ($loop->index < $currentStage ? 'Completed' : 'Upcoming') }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <details class="workflow-card workflow-disclosure document-history" open>
        <summary>Complaint history <span>{{ $complaint->staffUpdates->count() + 1 }} {{ $complaint->staffUpdates->isEmpty() ? 'update' : 'updates' }}</span></summary>
        <br>
        <ol class="workflow-history document-timeline">
            @foreach($complaint->staffUpdates as $update)
                <li @class(['document-timeline-event', 'is-latest' => $loop->first])>
                    <div class="document-timeline-heading">
                        <h3>Office updated complaint</h3>
                        <time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->setTimezone('Asia/Manila')->format('M d, Y · H:i') }}</time>
                    </div>
                </li>
            @endforeach
            <li @class(['document-timeline-event', 'is-latest' => $complaint->staffUpdates->isEmpty()])>
                <div class="document-timeline-heading">
                    <h3>Complaint submitted</h3>
                    <time datetime="{{ $complaint->created_at->toIso8601String() }}">{{ $complaint->created_at->setTimezone('Asia/Manila')->format('M d, Y · H:i') }}</time>
                </div>
            </li>
        </ol>
    </details>

    <section class="workflow-card">
        <h2>Complaint details</h2>
        @if($complaint->review_note)
            <div class="workflow-notice"><strong>Message from the office</strong><p class="portal-prose">{{ $complaint->review_note }}</p></div>
        @endif
        <p class="portal-prose">{{ $complaint->content }}</p>
        @if($complaint->googleMapsUrl())
            <section class="complaint-location-preview" aria-labelledby="complaint-location-title" data-complaint-location-preview>
                <h3 id="complaint-location-title">Location</h3>
                <div class="complaint-location-map" data-location-preview-map
                    data-latitude="{{ $complaint->latitude }}" data-longitude="{{ $complaint->longitude }}"
                    role="region" aria-label="Complaint location map" aria-describedby="complaint-location-preview-status"></div>
                <p id="complaint-location-preview-status" data-location-preview-status role="status"></p>
                <p><a href="{{ $complaint->googleMapsUrl() }}" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>
            </section>
        @endif
        <p>Submitted {{ $complaint->created_at->format('M d, Y H:i') }} · Updated {{ $complaint->updated_at->format('M d, Y H:i') }}</p>
    </section>
    <section class="workflow-card attachment-section">
        <div class="attachment-heading"><h2>Attachments <span>{{ $complaint->getMedia('document')->count() + (int) filled($complaint->attachment_url) }}</span></h2></div>
        <div class="attachment-gallery">
            @foreach($complaint->getMedia('document') as $media)
                @php
                    $previewUrl = \Illuminate\Support\Facades\Storage::disk('private')->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(30));
                    $isImage = str_starts_with($media->mime_type, 'image/');
                @endphp
                <article class="attachment-tile">
                    <a class="attachment-preview" href="{{ $previewUrl }}" data-file-preview data-file-type="{{ $isImage ? 'image' : 'pdf' }}" data-file-name="{{ $media->file_name }}" aria-label="Preview {{ $media->file_name }}">
                        @if($isImage)<img src="{{ $previewUrl }}" alt="{{ $media->file_name }}" loading="lazy">@else<span class="attachment-pdf">PDF</span>@endif
                        <span class="attachment-preview-label">Preview</span>
                    </a>
                    <div class="attachment-info"><h3><a href="{{ $previewUrl }}" data-file-preview data-file-type="{{ $isImage ? 'image' : 'pdf' }}" data-file-name="{{ $media->file_name }}">{{ $media->file_name }}</a></h3></div>
                </article>
            @endforeach
            @if($complaint->attachment_url)
                <article class="attachment-tile"><a class="attachment-preview" href="{{ route('resident.complaints.attachment', $complaint) }}" data-file-preview data-file-type="image" data-file-name="Previous attachment">Preview previous attachment</a></article>
            @elseif(!$complaint->hasMedia('document'))<p>No attachments.</p>@endif
        </div>
    </section>
    @include('resident.attachment-preview-dialog')
@endsection
@push('scripts')
    @if($complaint->googleMapsUrl())
        @vite('resources/js/complaint-location.js')
    @endif
    <script src="{{ asset('resident/js/documents.js') }}?v={{ filemtime(public_path('resident/js/documents.js')) }}" defer></script>
@endpush
