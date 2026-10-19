@extends('layouts.resident')
@section('title', 'My Complaints')
@section('main-class', 'documents-main')
@section('content')
    <section class="greeting page-head">
        <div>
            <h1>My Complaints</h1>
            <p>Track your complaints and their status.</p>
        </div>
        <button class="request-new" type="button" data-open-dialog="complaintDialog">File a Complaint</button>
    </section>
    <p class="documents-note">Report an issue to the barangay office and follow its progress here.</p>
    <section class="documents-panel" aria-label="My Complaints">
        @include('resident.table-toolbar', ['label' => 'My Complaints'])
        <div class="document-table-scroll" role="region" aria-label="My Complaints" tabindex="0">
            <table class="document-table">
                <thead>
                    <tr>
                        <th scope="col">Complaint No.</th>
                        <th scope="col">Category</th>
                        <th scope="col">Date Submitted</th>
                        <th scope="col">Last Updated</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td>{{ 'CMP-' . $complaint->id }}</td>
                            <td>{{ $complaint->complaint_type }}</td>
                            <td>{{ $complaint->created_at->format('M d, Y') }}</td>
                            <td>{{ $complaint->updated_at->format('M d, Y') }}</td>
                            <td>
                                @include('resident.status', ['status' => $complaint->status])
                            </td>
                            <td>
                                <button type="button" class="document-details-button"
                                    data-open-dialog="details-{{ $complaint->id }}">Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="documents-empty">No complaints found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer">
            <p role="status">{{ $complaints->total() }} records</p>
            @include('resident.pagination', ['paginator' => $complaints])
        </div>
    </section>
    @foreach ($complaints as $complaint)
        <dialog id="details-{{ $complaint->id }}" class="document-dialog"
            aria-labelledby="details-title-{{ $complaint->id }}">
            <div class="document-dialog-head">
                <h2 id="details-title-{{ $complaint->id }}">CMP-{{ $complaint->id }}</h2>
                <button type="button" data-close-dialog>Close</button>
            </div>
            <dl>
                <div>
                    <dt>Category</dt>
                    <dd>{{ $complaint->complaint_type }}</dd>
                </div>
                <div>
                    <dt>Complaint</dt>
                    <dd class="portal-prose">{{ $complaint->content }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $complaint->status->getLabel() }}</dd>
                </div>
                <div>
                    <dt>Last Updated</dt>
                    <dd>{{ $complaint->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
            @if ($complaint->attachment_url)
                <a class="btn" href="{{ route('resident.complaints.attachment', $complaint) }}">View attachment</a>
            @endif
        </dialog>
    @endforeach
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
        <form id="complaintForm" class="complaint-form" method="POST" enctype="multipart/form-data"
            action="{{ route('resident.complaints.store') }}">
            @csrf
            <p class="documents-note">Your complaint will be sent to the barangay office.</p>
            <label for="complaint_type">Category</label>
            <input id="complaint_type" list="complaintCategories" name="complaint_type" required maxlength="255"
                value="{{ old('complaint_type') }}" placeholder="Select or enter a category">
            <datalist id="complaintCategories">
                @foreach (['Noise', 'Sanitation', 'Public Safety', 'Roads and Infrastructure', 'Barangay Services', 'Other'] as $category)
                    <option value="{{ $category }}">
                @endforeach
            </datalist>
            <label for="content">Complaint details</label>
            <textarea id="content" name="content" rows="9" required maxlength="10000"
                placeholder="Describe what happened, where and when, and how it affected you.">{{ old('content') }}</textarea>
            <div class="complaint-image-preview" id="complaintImagePreview" hidden>
                <img id="complaintImage" alt="Selected complaint attachment">
                <div>
                    <p id="complaintImageName">
                    </p>
                    <button type="button" id="removeComplaintImage">Remove image</button>
                </div>
            </div>
            <p class="complaint-image-error" id="complaintImageError" role="alert">
            </p>
            <div class="complaint-form-actions">
                <label for="attachment">
                    <span data-modal-icon="image-plus" aria-hidden="true">
                    </span>Photo (optional, JPG / PNG / WebP, up to 5 MB)<input id="attachment" name="attachment"
                        type="file" accept="image/jpeg,image/png,image/webp">
                </label>
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
