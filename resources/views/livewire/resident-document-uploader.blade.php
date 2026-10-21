<div class="resident-media-upload" wire:loading.attr="data-upload-busy">
    {{ $this->form }}
    @if($hasUpload)
        <input type="hidden" data-upload-token name="uploads[{{ $kind }}]" value="{{ $token }}">
        <span class="upload-saved" role="status">Ready to submit</span>
    @endif
    <span wire:loading role="status">Saving file…</span>
</div>
