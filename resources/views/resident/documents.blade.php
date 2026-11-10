@extends('layouts.resident')
@section('title', 'My Documents')
@section('main-class', 'documents-main documents-list-page')
@section('content')
    <section class="greeting page-head">
        <div>
            <h2>My Documents</h2>
            <p>Track your barangay document requests.</p>
        </div>
        <a class="request-new" href="{{ route('resident.documents.create') }}">Request New</a>
    </section>
    <section class="documents-panel" aria-label="My Documents">
        <form class="documents-toolbar" method="GET" action="{{ route('resident.documents') }}">
            <div class="document-search">
                <label for="recordSearch">Search documents</label>
                <input type="search" id="recordSearch" name="search" value="{{ request('search') }}" placeholder="Search request, type, purpose or status" maxlength="100">
            </div>
            <button class="btn" type="submit">Search</button>
            @if((request()->filled('search') || request()->filled('document_type_id')))<a class="documents-clear" href="{{ route('resident.documents') }}">Clear</a>@endif
            <div class="document-type-filter">
                <select id="recordType" name="document_type_id">
                    <option value="">All document types</option>
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) request('document_type_id') === (string) $type->id)>{{ $type->document_type_name }}</option>
                    @endforeach
                </select>
            </div>
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
                <thead><tr><th scope="col">Request No.</th><th scope="col">Document Type</th><th scope="col">Date Applied</th><th scope="col">Purpose</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td data-label="Request No." class="document-number">{{ $document->reference }}</td>
                            <td data-label="Document Type" class="document-type">{{ $document->documentType->document_type_name }}</td>
                            <td data-label="Date Applied" class="document-date">{{ $document->created_at->format('F j, Y') }}</td>
                            <td data-label="Purpose" class="document-purpose-cell">{{ $document->purpose }}</td>
                            <td data-label="Status"><div class="document-state" data-status="{{ $document->status->value }}">@include('resident.status', ['status' => $document->status])</div></td>
                           <td data-label="Actions" class="document-action">
                                <a class="btn-sm" href="{{ route('resident.documents.show', $document) }}" aria-label="View {{ $document->reference }}">
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
                            <span class="documents-empty-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6M8 13h8M8 17h5" />
                                </svg>
                            </span>
                            <strong>{{ (request()->filled('search') || request()->filled('document_type_id')) ? 'No matching requests' : 'Your requests will appear here' }}</strong>
                            <p>{{ (request()->filled('search') || request()->filled('document_type_id')) ? 'Try another search or document type.' : 'Start by choosing the document you need.' }}</p>
                            @if(request()->filled('search') || request()->filled('document_type_id'))
                                <a href="{{ route('resident.documents') }}">Back to requests →</a>
                            @else
                                <form method="GET" action="{{ route('resident.documents.create') }}">
                                    <button class="btn" type="submit">Create a request →</button>
                                </form>
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer">
            <p role="status">Showing {{ $documents->firstItem() ?? 0 }}–{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() }} entries</p>
            @include('resident.pagination', ['paginator' => $documents, 'alwaysShow' => true])
        </div>
    </section>
@endsection

@push('scripts')

    <script src="{{ asset('resident/js/documents.js') }}?v={{ filemtime(public_path('resident/js/documents.js')) }}" defer></script>

@endpush
