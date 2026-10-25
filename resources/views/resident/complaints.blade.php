@extends('layouts.resident')
@section('title', 'My Complaints')
@section('main-class', 'documents-main documents-list-page')
@section('uses-resident-upload', true)
@section('content')
    <section class="greeting page-head">
        <div>
            <h2>My Complaints</h2>
            <p>Track your complaints and barangay replies.</p>
        </div>
        <button class="request-new" type="button" data-open-dialog="complaintDialog">File a Complaint</button>
    </section>
    <section class="documents-panel" aria-label="My Complaints">
        <form class="documents-toolbar" method="GET" action="{{ route('resident.complaints') }}">
            <div class="document-search">
                <label for="recordSearch">Search complaints</label>
                <input type="search" id="recordSearch" name="search" value="{{ request('search') }}" placeholder="Search number, category, content or status" maxlength="255">
            </div>
            <button class="btn" type="submit">Search</button>
            @if(request()->filled('search'))<a class="documents-clear" href="{{ route('resident.complaints') }}">Clear</a>@endif
            <div class="document-page-size">
                <label for="pageSize">Show</label>
                <select id="pageSize" name="per_page">
                    @foreach ([2, 5, 10, 25] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
                <span>entries</span>
            </div>
        </form>
        <div class="document-table-scroll">
            <table class="document-table documents-request-table">
                <thead><tr><th scope="col">Complaints No</th><th scope="col">Category</th><th scope="col">Date Submit</th><th scope="col">Date Replyed</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td data-label="Complaints No" class="document-number">{{ $complaint->reference }}</td>
                            <td data-label="Category" class="document-type">{{ $complaint->complaint_type }}</td>
                            <td data-label="Date Submit" class="document-date">{{ $complaint->created_at->format('F j, Y') }}</td>
                            <td data-label="Date Replyed" class="document-date">{{ $complaint->latestStaffUpdate?->created_at?->format('F j, Y') ?? '—' }}</td>
                            <td data-label="Status"><div class="document-state" data-status="{{ $complaint->status->value }}">@include('resident.status', ['status' => $complaint->status])</div></td>
                            <td data-label="Actions" class="document-action">
                                <a class="btn-sm" href="{{ route('resident.complaints.show', $complaint) }}" aria-label="View {{ $complaint->reference }}">
                                <svg class="btn-icon"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="documents-empty">
                            <span class="documents-empty-icon" aria-hidden="true">▤</span>
                            <strong>{{ request()->filled('search') ? 'No matching complaints' : 'Your complaints will appear here' }}</strong>
                            <p>{{ request()->filled('search') ? 'Try another category or reference number.' : 'Report an issue to the barangay office.' }}</p>
                            @if(request()->filled('search'))<a href="{{ route('resident.complaints') }}">Clear search →</a>@endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer"><p role="status">Showing {{ $complaints->firstItem() ?? 0 }}–{{ $complaints->lastItem() ?? 0 }} of {{ $complaints->total() }} entries</p>@include('resident.pagination', ['paginator' => $complaints, 'alwaysShow' => true])</div>
    </section>

    {{-- modal dialog for filing a complaint --}}
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
            <div class="complaint-form-grid">
                <div class="complaint-form-details">
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
                </div>
                <div class="complaint-form-location">
                    <fieldset class="complaint-location" data-complaint-location>
                        <legend>Location (optional)</legend>
                        <p id="complaintLocationHelp">Mark where the issue happened, use your current location, or enter coordinates. Click the map or drag the pin to adjust. With the map focused, use arrow keys to move and Enter to select its center.</p>
                        <div class="complaint-location-actions">
                            <button type="button" data-location-toggle aria-controls="complaintLocationMap" aria-expanded="false">Choose on map</button>
                            <button type="button" data-location-locate>Use my current location</button>
                            <button type="button" data-location-clear>Clear location</button>
                        </div>
                        <div id="complaintLocationMap" class="complaint-location-map" data-location-map hidden
                            role="region" aria-label="Complaint location map" aria-describedby="complaintLocationHelp"></div>
                        <div class="complaint-location-coordinates">
                            <div>
                                <label for="complaintLatitude">Latitude</label>
                                <input id="complaintLatitude" name="latitude" type="number" min="-90" max="90" step="any"
                                    value="{{ is_scalar(old('latitude')) ? old('latitude') : '' }}" placeholder="e.g. 14.4297" data-location-latitude>
                                @error('latitude')<p class="complaint-location-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="complaintLongitude">Longitude</label>
                                <input id="complaintLongitude" name="longitude" type="number" min="-180" max="180" step="any"
                                    value="{{ is_scalar(old('longitude')) ? old('longitude') : '' }}" placeholder="e.g. 120.9367" data-location-longitude>
                                @error('longitude')<p class="complaint-location-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p class="complaint-location-status" data-location-status role="status" aria-live="polite">No location selected.</p>
                    </fieldset>
                </div>
            </div>
            <div class="complaint-form-actions">
                <button type="submit">
                    <span data-modal-icon="send" aria-hidden="true">
                    </span>Submit complaint</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
    @vite('resources/js/complaint-location.js')
    <script src="{{ asset('resident/js/complaints.js') }}" defer></script>

    @if (session('complaint_submitted'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Submission Successful',
                text: 'Your complaint has been submitted successfully.',
                confirmButtonText: 'Back to Complaints',
                confirmButtonColor: '#3b82f6',
                allowOutsideClick: false,
                timer: 3000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = @json(route('resident.complaints'));
            });
        </script>
    @endif
@endpush
