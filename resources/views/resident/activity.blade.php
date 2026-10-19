@extends('layouts.resident')
@section('title', 'My Activity')
@section('main-class', 'activity-main')
@section('content')
    <section class="greeting">
        <h2>My Activity</h2>
        <p>Track your recent requests and complaints.</p>
    </section>
    <div class="activity-toolbar">
        <div>
            <label for="activityFilter">Filter activity</label>
            <select id="activityFilter">
                <option value="all">All activity</option>
                <option value="documents">Document requests</option>
                <option value="complaints">Complaints</option>
                <option value="completed">Completed / Resolved</option>
                <option value="active">In progress / Pending</option>
            </select>
        </div>
        <span id="activityCount" role="status">{{ $activity->count() }} activities</span>
    </div>
    <p class="activity-sort">Latest 10 activities · Select an event to view its details</p>
    <ol id="activityFeed" class="activity-timeline" aria-label="Recent activity">
        @foreach ($activity as $record)
            @php
                $isDocument = $record instanceof \App\Models\DocumentRequest;
                $kind = $isDocument ? 'documents' : 'complaints';
                $reference = ($isDocument ? 'DOC-' : 'CMP-') . $record->id;
                $title = $isDocument ? $record->documentType->document_type_name : $record->complaint_type;
                $content = $isDocument ? $record->purpose : $record->content;
            @endphp
            <li class="activity-event" data-kind="{{ $kind }}"
                data-state="{{ in_array($record->status->value, ['released', 'resolved']) ? 'completed' : (in_array($record->status->value, ['pending', 'processing', 'in_progress', 'ready']) ? 'active' : 'rejected') }}">
                <time
                    datetime="{{ $record->updated_at->toIso8601String() }}">{{ $record->updated_at->format('M d, Y') }}</time>
                <span class="activity-marker" aria-hidden="true">
                    <span data-modal-icon="{{ $isDocument ? 'file-text' : 'message-square-warning' }}">
                    </span>
                </span>
                <button type="button" class="activity-event-button {{ $kind }}"
                    data-open-dialog="activity-{{ $reference }}">
                    <span class="activity-event-top">
                        <span class="activity-kind">{{ $isDocument ? 'Document request' : 'Complaint' }}</span>
                        @include('resident.status', ['status' => $record->status])
                    </span>
                    <span class="activity-event-title">{{ $title }}</span>
                    <span class="activity-event-preview">{{ Str::limit($content, 140) }}</span>
                    <span class="activity-meta">{{ $reference }} · View details →</span>
                </button>
            </li>
        @endforeach
    </ol>
    <p id="activityEmpty" @if ($activity->isNotEmpty()) hidden @endif>No activity found. Your document requests and
        complaints will appear here.</p>
    @foreach ($activity as $record)
        @php($isDocument = $record instanceof \App\Models\DocumentRequest)
        @php($reference = ($isDocument ? 'DOC-' : 'CMP-') . $record->id)
        <dialog id="activity-{{ $reference }}" class="activity-drawer"
            aria-labelledby="activity-title-{{ $reference }}">
            <div class="drawer-header">
                <span>Activity details</span>
                <button type="button" data-close-dialog aria-label="Close activity details">×</button>
            </div>
            <div class="drawer-content">
                <p class="activity-kind">{{ $isDocument ? 'Document request' : 'Complaint' }}</p>
                <h2 id="activity-title-{{ $reference }}">
                    {{ $isDocument ? $record->documentType->document_type_name : $record->complaint_type }}</h2>
                <p class="activity-meta">{{ $reference }}</p>
                <dl class="activity-details">
                    <div>
                        <dt>Submitted</dt>
                        <dd>{{ $record->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt>Last updated</dt>
                        <dd>{{ $record->updated_at->format('M d, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt>{{ $isDocument ? 'Purpose' : 'Complaint' }}</dt>
                        <dd class="portal-prose">{{ $isDocument ? $record->purpose : $record->content }}</dd>
                    </div>
                </dl>
                <section class="activity-status-panel">
                    <h3>Status</h3>
                    @include('resident.status', ['status' => $record->status])
                </section>
                <a class="btn" href="{{ route($isDocument ? 'resident.documents' : 'resident.complaints') }}">View all
                    {{ $isDocument ? 'document requests' : 'complaints' }}</a>
            </div>
        </dialog>
    @endforeach
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/activity.js') }}" defer></script>
@endpush
