@php
    $complaint = $getRecord();
@endphp

<section aria-labelledby="complaint-location-title" style="display: grid; gap: .75rem;" data-complaint-location-preview>
    <h3 id="complaint-location-title" style="font-size: .875rem; font-weight: 500;">Location</h3>

    @if($complaint?->latitude !== null && $complaint?->longitude !== null)
        <div wire:ignore>
            <div data-location-preview-map
                data-latitude="{{ $complaint->latitude }}" data-longitude="{{ $complaint->longitude }}"
                style="height: 300px; width: 100%; border-radius: .5rem; isolation: isolate;"
                role="region" aria-label="Complaint location map" aria-describedby="complaint-location-preview-status"></div>
            <p id="complaint-location-preview-status" data-location-preview-status role="status" style="font-size: .875rem;"></p>
        </div>
        <p style="font-size: .875rem;">Latitude: {{ $complaint->latitude }} · Longitude: {{ $complaint->longitude }}</p>
        <x-filament::link :href="$complaint->googleMapsUrl()" target="_blank" rel="noopener noreferrer" icon="heroicon-o-arrow-top-right-on-square">
            Open in Google Maps
        </x-filament::link>

        @assets
            @vite('resources/js/complaint-location.js')
        @endassets
    @else
        <p style="font-size: .875rem;">No location provided</p>
    @endif
</section>
