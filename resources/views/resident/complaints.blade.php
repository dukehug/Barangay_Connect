@extends('layouts.resident')
@section('title', 'My Complaints')
@section('main-class', 'documents-main documents-list-page')
@section('uses-resident-upload', true)
@section('content')
    <section class="greeting page-head">
        <div>
            <h1>My Complaints</h1>
            <p>Your complaints, all in one place.</p>
        </div>
        <button class="request-new" type="button" data-open-dialog="complaintDialog"><span aria-hidden="true">+</span> New complaint</button>
    </section>
    <ol class="documents-guide" aria-label="How complaints work">
        <li><span>1</span> Submit complaint</li><li><span>2</span> Track updates</li><li><span>3</span> View resolution</li>
    </ol>
    <section class="documents-panel" aria-label="My Complaints">
        <form class="documents-toolbar" method="GET" action="{{ route('resident.complaints') }}">
            <div class="document-search">
                <label class="document-sr-only" for="recordSearch">Search my complaints</label>
                <input type="search" id="recordSearch" name="search" value="{{ request('search') }}" placeholder="Search complaints…" maxlength="255">
            </div>
            <button class="btn" type="submit">Search</button>
            @if(request()->filled('search'))<a class="documents-clear" href="{{ route('resident.complaints') }}">Clear</a>@endif
            <div class="document-page-size">
                <label for="pageSize">Per page</label>
                <select id="pageSize" name="per_page">
                    @foreach ([2, 5, 10, 25] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <div class="document-table-scroll">
            <table class="document-table documents-request-table">
                <thead><tr><th scope="col">Complaint</th><th scope="col">Submitted</th><th scope="col">Status</th><th scope="col"><span class="document-sr-only">Action</span></th></tr></thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td>
                                <a class="document-name" href="{{ route('resident.complaints.show', $complaint) }}">{{ $complaint->complaint_type }}</a>
                                <span class="document-reference">CMP-{{ $complaint->id }}</span>
                                <span class="document-purpose">{{ Str::limit($complaint->content, 65) }}</span>
                            </td>
                            <td class="document-date">{{ $complaint->created_at->format('M d, Y') }}</td>
                            <td><div class="document-state" data-status="{{ $complaint->status->value }}">@include('resident.status', ['status' => $complaint->status])</div></td>
                            <td class="document-action"><a class="document-details-button" href="{{ route('resident.complaints.show', $complaint) }}" aria-label="View CMP-{{ $complaint->id }}">View details <span aria-hidden="true">→</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="documents-empty">
                            <span class="documents-empty-icon" aria-hidden="true">▤</span>
                            <strong>{{ request()->filled('search') ? 'No matching complaints' : 'Your complaints will appear here' }}</strong>
                            <p>{{ request()->filled('search') ? 'Try another category or reference number.' : 'Report an issue to the barangay office.' }}</p>
                            @if(request()->filled('search'))<a href="{{ route('resident.complaints') }}">Clear search →</a>@endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer"><p role="status">{{ $complaints->total() }} {{ Str::plural('complaint', $complaints->total()) }}</p>@include('resident.pagination', ['paginator' => $complaints])</div>
    </section>
    <dialog id="complaintDialog" class="request-dialog complaint-dialog" aria-labelledby="formTitle"
        @if ($errors->any()) data-reopen @endif>
        <header class="request-dialog-header">
            <h2 id="formTitle">
                <span data-modal-icon="message-square-warning" aria-hidden="true">
                </span>File a Complaint
            </h2>
            <button class="request-close" type="button" data-close-dialog aria-label="Close form">
                <span data-modal-icon="x" aria-hidden="true">
                </span>
            </button>
        </header>
        @if ($errors->any())
            @include('resident.messages')
        @endif
        <form id="complaintForm" data-resident-uploads class="complaint-form" method="POST" enctype="multipart/form-data"
            action="{{ route('resident.complaints.store') }}">
            @csrf
            <p class="documents-note">Your complaint will be sent to the barangay office.</p>
            <label for="complaint_type">Category</label>
            <select id="complaint_type" name="complaint_type" required>
                <option value="">Choose a category</option>
                @foreach ($complaintTypes as $type)
                    <option value="{{ $type->name }}" @selected(old('complaint_type') === $type->name)>{{ $type->name }}</option>
                @endforeach
            </select>
            @if($complaintTypes->isEmpty())<p>No categories available. Please contact the barangay office.</p>@endif
            <label for="content">Complaint details</label>
            <textarea id="content" name="content" rows="9" required maxlength="10000"
                placeholder="Describe what happened, where and when, and how it affected you.">{{ old('content') }}</textarea>
            <p class="upload-kind-label">Attachments (optional)</p>
            <p>PDF, JPG or PNG · Up to 10 MB each. Select multiple files.</p>
            <livewire:resident-document-uploader kind="complaint_attachment" :token="old('uploads.complaint_attachment')" />
            <div class="complaint-form-actions">
                <button type="submit">
                    <span data-modal-icon="send" aria-hidden="true">
                    </span>Submit complaint</button>
            </div>
        </form>
    </dialog>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/complaints.js') }}" defer></script>
@endpush
