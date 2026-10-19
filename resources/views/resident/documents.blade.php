@extends('layouts.resident')
@section('title', 'My Documents')
@section('main-class', 'documents-main')
@section('content')
    <section class="greeting page-head">
        <div>
            <h1>My Documents</h1>
            <p>Track your barangay document requests.</p>
        </div>
        <button class="request-new" type="button" data-open-dialog="requestDialog">Request New</button>
    </section>
    <p class="documents-note">Submit your application to the barangay office. Documents are available for pickup when ready.
    </p>
    <section class="documents-panel" aria-label="My Documents">
        @include('resident.table-toolbar', ['label' => 'My Documents'])
        <div class="document-table-scroll" role="region" aria-label="My Documents" tabindex="0">
            <table class="document-table">
                <thead>
                    <tr>
                        <th scope="col">Request No.</th>
                        <th scope="col">Document Type</th>
                        <th scope="col">Date Applied</th>
                        <th scope="col">Purpose</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>{{ 'DOC-' . $document->id }}</td>
                            <td>{{ $document->documentType->document_type_name }}</td>
                            <td>{{ $document->created_at->format('M d, Y') }}</td>
                            <td>{{ Str::limit($document->purpose, 80) }}</td>
                            <td>
                                @include('resident.status', ['status' => $document->status])
                            </td>
                            <td>
                                <button type="button" class="document-details-button"
                                    data-open-dialog="details-{{ $document->id }}">Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="documents-empty">No document requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer">
            <p role="status">{{ $documents->total() }} records</p>
            @include('resident.pagination', ['paginator' => $documents])
        </div>
    </section>
    @foreach ($documents as $document)
        <dialog id="details-{{ $document->id }}" class="document-dialog"
            aria-labelledby="details-title-{{ $document->id }}">
            <div class="document-dialog-head">
                <h2 id="details-title-{{ $document->id }}">DOC-{{ $document->id }}</h2>
                <button type="button" data-close-dialog>Close</button>
            </div>
            <dl>
                <div>
                    <dt>Document Type</dt>
                    <dd>{{ $document->documentType->document_type_name }}</dd>
                </div>
                <div>
                    <dt>Purpose</dt>
                    <dd class="portal-prose">{{ $document->purpose }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $document->status->getLabel() }}</dd>
                </div>
                <div>
                    <dt>Last Updated</dt>
                    <dd>{{ $document->updated_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </dialog>
    @endforeach
    <dialog id="requestDialog" class="request-dialog " aria-labelledby="formTitle"
        @if ($errors->any()) data-reopen @endif>
        <header class="request-dialog-header">
            <h2 id="formTitle">
                <span data-modal-icon="clipboard-list" aria-hidden="true">
                </span>Application Form
            </h2>
            <button class="request-close" type="button" data-close-dialog aria-label="Close form">
                <span data-modal-icon="x" aria-hidden="true">
                </span>
            </button>
        </header>
        <div class="request-resident">
            <img src="{{ auth('resident')->user()->profile_image_url ? route('resident.profile.photo') : asset('resident/images/profile-placeholder.svg') }}"
                alt="">
            <dl>
                @foreach (['Name' => auth('resident')->user()->full_name, 'Resident ID' => 'RS-' . auth('resident')->id(), 'Address' => auth('resident')->user()->resident_address] as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
        @if ($errors->any())
            @include('resident.messages')
        @endif
        <form id="requestForm" method="POST" action="{{ route('resident.documents.store') }}">
            @csrf
            <section class="request-section">
                <h3>
                    <span data-modal-icon="package-check" aria-hidden="true">
                    </span>Pickup Information
                </h3>
                <p>For pickup at the barangay office. Delivery is not available.</p>
                <p>Contact: {{ auth('resident')->user()->contact_number ?: 'Not specified' }} ·
                    {{ auth('resident')->user()->email }}</p>
                <a href="{{ route('resident.profile') }}">Update contact
                    information</a>
            </section>
            <section class="request-section">
                <h3>
                    <span data-modal-icon="files" aria-hidden="true">
                    </span>Document / Service Requested
                </h3>
                <label for="document_type_id">Document type</label>
                <select name="document_type_id" id="document_type_id" required>
                    <option value="">Select a document</option>
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>{{ $type->document_type_name }} —
                            ₱{{ $type->document_type_fee ?? '0.00' }}{{ $type->processing_days ? ' · ' . $type->processing_days . ' processing days' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="request-help">Submit one request per document. Present any required supporting documents during
                    processing.</p>
                <label for="purpose">Purpose of Request</label>
                <textarea id="purpose" name="purpose" rows="3" required maxlength="5000">{{ old('purpose') }}</textarea>
            </section>
            <div class="request-submit">
                <button type="submit" @disabled($documentTypes->isEmpty())>
                    <span data-modal-icon="send" aria-hidden="true">
                    </span>Send Application</button>
            </div>
            @if ($documentTypes->isEmpty())
                <p class="documents-note">No document types are available yet.</p>
            @endif
        </form>
    </dialog>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/documents.js') }}" defer></script>
@endpush
