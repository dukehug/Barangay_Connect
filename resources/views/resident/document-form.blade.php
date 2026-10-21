@extends('layouts.resident')
@section('title', $document->exists ? 'Revise Application' : 'New Application')
@section('main-class', 'documents-main')
@section('uses-resident-upload', true)
@section('content')
    <section class="greeting page-head">
        <div><h1>{{ $document->exists ? 'Revise '.$document->reference : 'New Application' }}</h1><p>Choose a document, add details, then submit.</p></div>
        <a href="{{ $document->exists ? route('resident.documents.show', $document) : route('resident.documents') }}">Back to requests</a>
    </section>
    <form class="workflow-form" data-document-form data-resident-uploads method="POST" enctype="multipart/form-data" action="{{ $document->exists ? route('resident.documents.update', $document) : route('resident.documents.store') }}">
        @csrf
        @if($document->exists) @method('PUT') @endif
        <section class="workflow-card workflow-applicant">
            <h2>Applying as</h2>
            <p>{{ auth('resident')->user()->full_name }} · {{ auth('resident')->user()->resident_address }}</p>
            <p>{{ auth('resident')->user()->email }} · {{ auth('resident')->user()->contact_number }}</p>
            <a href="{{ route('resident.profile') }}">Edit profile</a>
        </section>
        <section class="workflow-card">
            <h2><span class="workflow-step">1</span> Request details</h2>
            <label for="document_type_id">Application type</label>
            <select name="document_type_id" id="document_type_id" required @disabled($document->exists)>
                <option value="">Choose an application type</option>
                @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}" data-category="{{ $document->exists ? $document->workflowCategory() : $type->category }}" @selected(old('document_type_id', $document->document_type_id) == $type->id)>{{ $type->document_type_name }}</option>
                @endforeach
            </select>
            @if($document->exists)<input type="hidden" name="document_type_id" value="{{ $document->document_type_id }}">@endif
            <div data-category-panel="clearance">
                <label for="application_kind">Application</label>
                <select id="application_kind" name="application_kind"><option value="new" @selected(old('application_kind', $document->application_kind) !== 'renewal')>New application</option><option value="renewal" @selected(old('application_kind', $document->application_kind) === 'renewal')>Renewal</option></select>
            </div>
            <div data-category-panel="clearance">
                <p class="workflow-notice">Pay at the Barangay Office after approval. Online payment is not available. Keep your Official Receipt for release.</p>
                <details class="workflow-help"><summary>Purpose examples</summary><p>Construction, repairs or road maintenance.</p></details>
            </div>
            <div data-category-panel="certification">
                <p class="workflow-notice">Requires at least 8 months in the barangay RBI registry. The office will verify this; website registration does not count.</p>
                <details class="workflow-help"><summary>Purpose examples</summary><p>Residency, employment, school, senior citizen or ID applications.</p></details>
            </div>
            <div data-category-panel="use_closure"><p>Request a street, equipment, or both. The office checks availability; you confirm any partial offer.</p></div>
            <label for="purpose">Purpose and details</label>
            <textarea id="purpose" name="purpose" rows="4" maxlength="5000" required placeholder="Describe the purpose, activity and any relevant location details.">{{ old('purpose', $document->purpose) }}</textarea>
        </section>
        <section class="workflow-card" data-category-panel="use_closure">
            <h2>Street / equipment requested</h2>
            <p>Set a quantity for items you need (1 for a street, 0 to skip). Times are Philippine time.</p>
            <p>Include setup and cleanup in your schedule. Equipment dates are pickup and return times.</p>
            @forelse($resources as $resource)
                @php
                    $existing = collect(old('reservations', $document->reservations->toArray()))->firstWhere('resource_item_id', $resource->id);
                    $start = $existing['starts_at'] ?? null;
                    $end = $existing['ends_at'] ?? null;
                @endphp
                <fieldset class="workflow-resource" data-resource-row>
                    <legend>{{ $resource->name }} · {{ $resource->kind === 'street' ? 'Street / road segment' : 'Equipment' }}</legend>
                    @if($resource->description)<p>{{ $resource->description }}</p>@endif
                    <input type="hidden" name="reservations[{{ $loop->index }}][resource_item_id]" value="{{ $resource->id }}">
                    <div class="workflow-grid">
                        <label>Quantity <input type="number" data-resource-quantity name="reservations[{{ $loop->index }}][requested_quantity]" min="0" max="{{ $resource->kind === 'street' ? 1 : 100000 }}" value="{{ $existing['requested_quantity'] ?? 0 }}"></label>
                        <label>{{ $resource->kind === 'street' ? 'Use starts' : 'Pickup time' }} <input type="datetime-local" name="reservations[{{ $loop->index }}][starts_at]" value="{{ $start ? \Carbon\CarbonImmutable::parse($start)->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') : '' }}"></label>
                        <label>{{ $resource->kind === 'street' ? 'Use ends' : 'Expected return' }} <input type="datetime-local" name="reservations[{{ $loop->index }}][ends_at]" value="{{ $end ? \Carbon\CarbonImmutable::parse($end)->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') : '' }}"></label>
                    </div>
                </fieldset>
            @empty
                <p class="workflow-notice">No streets or equipment are available for online requests yet. Please contact the Barangay Office.</p>
            @endforelse
        </section>
        <section class="workflow-card" data-supporting-documents>
            <h2><span class="workflow-step">2</span> Attach documents</h2>
            <p>PDF, JPG or PNG · Up to 10 MB each. Select multiple files.</p>
            @if($document->exists)<p>Previous uploads are saved. Add a new copy only if needed.</p>@endif
            @foreach(\App\Models\RequestAttachment::KINDS as $kind => $label)
                <div data-attachment-kind="{{ $kind }}">
                    <p class="upload-kind-label">{{ $label }}</p>
                    @foreach($document->attachments->where('kind', $kind) as $attachment)
                        <p><a href="{{ route('resident.documents.attachment', $attachment) }}">{{ $attachment->name }}</a> · {{ $attachment->created_at->setTimezone('Asia/Manila')->format('M d, Y H:i') }}</p>
                    @endforeach
                    <livewire:resident-document-uploader :kind="$kind" :token="old('uploads.'.$kind)" :key="'document-upload-'.$kind" />
                </div>
            @endforeach
        </section>
        <div class="workflow-actions"><button class="request-new" type="submit">{{ $document->exists ? 'Resubmit application' : 'Submit application' }}</button><p>Track review updates in My Documents.</p></div>
    </form>
@endsection
@push('scripts')<script src="{{ asset('resident/js/documents.js') }}" defer></script>@endpush
