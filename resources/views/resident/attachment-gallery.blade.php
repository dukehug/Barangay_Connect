<section class="workflow-card attachment-section" aria-labelledby="attachment-heading">
    <div class="attachment-heading">
        <h2 id="attachment-heading">Attachments <span>{{ $document->attachments->count() }}</span></h2>
        @if($document->isEditable())<a href="{{ route('resident.documents.edit', $document) }}">Add files →</a>@endif
    </div>
    <div class="attachment-gallery">
        @forelse($document->attachments as $attachment)
            <article class="attachment-tile">
                <a class="attachment-preview" href="{{ $attachment->previewUrl() }}" data-file-preview data-file-type="{{ $attachment->isImage() ? 'image' : 'pdf' }}" data-file-name="{{ $attachment->name }}" aria-label="Preview {{ $attachment->name }}">
                    @if($attachment->isImage())
                        <img src="{{ $attachment->previewUrl() }}" alt="{{ $attachment->name }}" loading="lazy">
                    @else
                        <span class="attachment-pdf" aria-hidden="true"><svg width="38" height="44" viewBox="0 0 24 28" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 1H3a2 2 0 0 0-2 2v22a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V10L14 1Z"/><path d="M14 1v9h9M6 16h12M6 21h8"/></svg>PDF</span>
                    @endif
                    <span class="attachment-preview-label">Preview</span>
                </a>
                <div class="attachment-info">
                    <h3><a href="{{ $attachment->previewUrl() }}" data-file-preview data-file-type="{{ $attachment->isImage() ? 'image' : 'pdf' }}" data-file-name="{{ $attachment->name }}" title="{{ $attachment->name }}">{{ $attachment->name }}</a></h3>
                    <p>{{ \App\Models\RequestAttachment::KINDS[$attachment->kind] ?? $attachment->kind }}</p>
                    <div class="attachment-controls">
                        <a href="{{ route('resident.documents.attachment', $attachment) }}" aria-label="Download {{ $attachment->name }}">Download</a>
                        @if($document->isEditable())
                            <form method="POST" action="{{ route('resident.documents.attachments.destroy', $attachment) }}" data-delete-attachment>
                                @csrf @method('DELETE')
                                <button type="submit" class="attachment-delete" aria-label="Delete {{ $attachment->name }}">Delete</button>
                            </form>
                        @endif
                        @if($document->attachments->count() > 1)
                            <form class="attachment-order" method="POST" action="{{ route('resident.documents.attachments.order', $attachment) }}">
                                @csrf @method('PATCH')
                                <button type="submit" name="direction" value="up" @disabled($loop->first) aria-label="Move {{ $attachment->name }} earlier">←</button>
                                <button type="submit" name="direction" value="down" @disabled($loop->last) aria-label="Move {{ $attachment->name }} later">→</button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p>No attachments yet.</p>
        @endforelse
    </div>
    @if($document->attachments->isNotEmpty())<p class="attachment-hint">Private files · Preview links expire in 30 minutes. Refresh to renew.</p>@endif
</section>
@include('resident.attachment-preview-dialog')
