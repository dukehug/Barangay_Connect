<div style="display: grid; gap: 1rem;">
    <p style="overflow-wrap: anywhere;">{{ $name }}</p>
    @if($isImage)
        <img src="{{ $url }}" alt="{{ $name }}" style="width: 100%; max-height: 70svh; object-fit: contain;" />
    @else
        <iframe src="{{ $url }}" title="{{ $name }}" style="width: 100%; height: 70svh; border: 0;"></iframe>
    @endif
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
        <x-filament::link :href="$url" :download="$name" icon="heroicon-o-arrow-down-tray">Download</x-filament::link>
        <p>Preview unavailable? <x-filament::link :href="$url" target="_blank" rel="noopener">Open file</x-filament::link></p>
    </div>
</div>
