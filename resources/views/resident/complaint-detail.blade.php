@extends('layouts.resident')
@section('title', 'CMP-'.$complaint->id)
@section('main-class', 'documents-main')
@section('content')
    <section class="greeting page-head"><div><h1>CMP-{{ $complaint->id }}</h1><p>{{ $complaint->complaint_type }}</p></div><a href="{{ route('resident.complaints') }}">Back to My Complaints</a></section>
    <section class="workflow-card">
        <div class="document-state" data-status="{{ $complaint->status->value }}">@include('resident.status', ['status' => $complaint->status])</div>
        @if($complaint->review_note)
            <div class="workflow-notice"><strong>Message from the office</strong><p class="portal-prose">{{ $complaint->review_note }}</p></div>
        @endif
        <h2>Complaint details</h2>
        <p class="portal-prose">{{ $complaint->content }}</p>
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
@push('scripts')<script src="{{ asset('resident/js/documents.js') }}?v={{ filemtime(public_path('resident/js/documents.js')) }}" defer></script>@endpush
