@extends('layouts.resident')
@section('title', 'My Documents')
@section('main-class', 'documents-main documents-list-page')
@section('content')
    <section class="greeting page-head">
        <div>
            <h1>My Documents</h1>
            <p>Your requests, all in one place.</p>
        </div>
        <a class="request-new" href="{{ route('resident.documents.create') }}"><span aria-hidden="true">+</span> New request</a>
    </section>
    <ol class="documents-guide" aria-label="How requests work">
        <li><span>1</span> Submit request</li>
        <li><span>2</span> Track updates</li>
        <li><span>3</span> Collect at the office</li>
    </ol>
    <section class="documents-panel" aria-label="My Documents">
        <form class="documents-toolbar" method="GET" action="{{ route('resident.documents') }}">
            <div class="document-search">
                <label class="document-sr-only" for="recordSearch">Search my documents</label>
                <input type="search" id="recordSearch" name="search" value="{{ request('search') }}" placeholder="Search requests…" maxlength="255">
            </div>
            <button class="btn" type="submit">Search</button>
            @if(request()->filled('search'))<a class="documents-clear" href="{{ route('resident.documents') }}">Clear</a>@endif
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
                <thead><tr><th scope="col">Document</th><th scope="col">Submitted</th><th scope="col">Status</th><th scope="col"><span class="document-sr-only">Action</span></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>
                                <a class="document-name" href="{{ route('resident.documents.show', $document) }}">{{ $document->documentType->document_type_name }}</a>
                                <span class="document-reference">DOC-{{ $document->id }}</span>
                                <span class="document-purpose">{{ Str::limit($document->purpose, 65) }}</span>
                            </td>
                            <td class="document-date">{{ $document->created_at->format('M d, Y') }}</td>
                            <td><div class="document-state" data-status="{{ $document->status->value }}">@include('resident.status', ['status' => $document->status])</div></td>
                            <td class="document-action">
                                <a class="document-details-button" href="{{ route('resident.documents.show', $document) }}" aria-label="View DOC-{{ $document->id }}">
                                    {{ match ($document->status) {
                                        \App\Enums\RequestStatus::NeedsRevision, \App\Enums\RequestStatus::Expired => 'Review & revise',
                                        \App\Enums\RequestStatus::AwaitingConfirmation => 'Review offer',
                                        \App\Enums\RequestStatus::AwaitingPayment => 'Payment details',
                                        \App\Enums\RequestStatus::Ready => 'Pickup details',
                                        default => 'View details',
                                    } }} <span aria-hidden="true">→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="documents-empty">
                            <span class="documents-empty-icon" aria-hidden="true">▤</span>
                            <strong>{{ request()->filled('search') ? 'No matching requests' : 'Your requests will appear here' }}</strong>
                            <p>{{ request()->filled('search') ? 'Try another name or reference number.' : 'Start by choosing the document you need.' }}</p>
                            <a href="{{ request()->filled('search') ? route('resident.documents') : route('resident.documents.create') }}">{{ request()->filled('search') ? 'Clear search' : 'Create a request' }} →</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="documents-footer">
            <p role="status">{{ $documents->total() }} {{ Str::plural('request', $documents->total()) }}</p>
            @include('resident.pagination', ['paginator' => $documents])
        </div>
    </section>
@endsection
@push('scripts')
    <script src="{{ asset('resident/js/documents.js') }}" defer></script>
@endpush
