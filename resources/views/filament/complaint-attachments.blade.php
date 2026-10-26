@php($complaint = $getRecord())
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); gap: 1rem;">
    @foreach($complaint->getMedia('document') as $media)
        @php($url = \Illuminate\Support\Facades\Storage::disk('private')->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(30)))
        <button type="button" wire:click="mountAction('previewAttachment', { media: {{ $media->id }} })" aria-label="Preview {{ $media->file_name }}" style="display: grid; gap: .75rem; padding: 1rem; border: 1px solid #94a3b8; border-radius: .5rem; cursor: pointer; text-align: left;">
            <span style="height: 160px; display: grid; place-items: center; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                @if(str_starts_with($media->mime_type, 'image/'))
                    <img src="{{ $url }}" alt="{{ $media->file_name }}" loading="lazy" style="width: 100%; height: 160px; object-fit: contain;" />
                @else
                    <span style="color: #9f3a41; font-weight: 600;">▤ PDF</span>
                @endif
            </span>
            <span style="overflow-wrap: anywhere;">{{ $media->file_name }}</span>
            <span>Preview</span>
        </button>
    @endforeach
    @if($complaint->attachment_url)
        <button type="button" wire:click="mountAction('previewAttachment')" aria-label="Preview {{ basename($complaint->attachment_url) }}" style="display: grid; gap: .75rem; padding: 1rem; border: 1px solid #94a3b8; border-radius: .5rem; cursor: pointer; text-align: left;">
            <img src="{{ route('admin.complaints.attachment', $complaint) }}" alt="{{ basename($complaint->attachment_url) }}" loading="lazy" style="width: 100%; height: 160px; object-fit: contain;" />
            <span style="overflow-wrap: anywhere;">{{ basename($complaint->attachment_url) }}</span>
            <span>Preview</span>
        </button>
    @elseif(! $complaint->hasMedia('document'))
        <p>No uploaded attachments.</p>
    @endif
</div>
