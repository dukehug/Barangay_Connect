import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const picker = document.querySelector('[data-complaint-location]');

if (picker) {
    const latitude = picker.querySelector('[data-location-latitude]');
    const longitude = picker.querySelector('[data-location-longitude]');
    const mapElement = picker.querySelector('[data-location-map]');
    const toggle = picker.querySelector('[data-location-toggle]');
    const locate = picker.querySelector('[data-location-locate]');
    const clear = picker.querySelector('[data-location-clear]');
    const status = picker.querySelector('[data-location-status]');
    const dialog = picker.closest('dialog');
    let map;
    let marker;
    let locationRequest = 0;

    const coordinates = () => {
        if (latitude.value.trim() === '' || longitude.value.trim() === '') return null;
        const lat = Number(latitude.value);
        const lng = Number(longitude.value);
        return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? [lat, lng] : null;
    };

    const cancelLocationRequest = () => {
        locationRequest += 1;
        locate.disabled = false;
        locate.removeAttribute('aria-busy');
    };

    const updateMarker = () => {
        const point = coordinates();
        longitude.required = latitude.value !== '';
        latitude.required = longitude.value !== '';
        clear.disabled = latitude.value === '' && longitude.value === '';
        if (!point) {
            if (marker) marker.remove();
            marker = null;
            status.textContent = clear.disabled ? 'No location selected.' : 'Enter both a valid latitude and longitude.';
            return;
        }
        if (map) {
            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = L.marker(point, {
                    draggable: true,
                    title: 'Complaint location — drag to adjust',
                    alt: 'Selected complaint location',
                    icon: L.icon({
                        iconUrl: markerIcon,
                        iconRetinaUrl: markerIconRetina,
                        shadowUrl: markerShadow,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        shadowSize: [41, 41],
                    }),
                }).addTo(map);
                marker.on('dragend', () => selectPoint(marker.getLatLng()));
            }
        }
        status.textContent = `Selected location: ${point[0].toFixed(7)}, ${point[1].toFixed(7)}.`;
    };

    const selectPoint = point => {
        cancelLocationRequest();
        const wrapped = L.latLng(point).wrap();
        latitude.value = Math.max(-90, Math.min(90, wrapped.lat)).toFixed(7);
        longitude.value = wrapped.lng.toFixed(7);
        updateMarker();
    };

    const showMap = () => {
        mapElement.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        toggle.textContent = 'Hide map';
        if (!map) {
            map = L.map(mapElement, {scrollWheelZoom: false}).setView(coordinates() ?? [14.4297, 120.9367], coordinates() ? 17 : 13);
            const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            }).addTo(map);
            tiles.on('tileerror', () => {
                status.textContent = 'Some map tiles could not load. You can still use your current location or enter coordinates.';
            });
            map.on('click', event => selectPoint(event.latlng));
            mapElement.addEventListener('keydown', event => {
                if (event.key === 'Enter' && event.target === mapElement) {
                    event.preventDefault();
                    event.stopPropagation();
                    selectPoint(map.getCenter());
                }
            });
        }
        map.invalidateSize();
        updateMarker();
    };

    toggle.addEventListener('click', () => {
        if (mapElement.hidden) {
            showMap();
        } else {
            mapElement.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = 'Choose on map';
        }
    });

    locate.addEventListener('click', () => {
        if (!window.isSecureContext || !navigator.geolocation) {
            status.textContent = 'Current location is unavailable. Use HTTPS, choose a point on the map, or enter coordinates.';
            return;
        }
        const request = ++locationRequest;
        locate.disabled = true;
        locate.setAttribute('aria-busy', 'true');
        clear.disabled = false;
        status.textContent = 'Finding your location…';
        navigator.geolocation.getCurrentPosition(position => {
            if (request !== locationRequest) return;
            selectPoint([position.coords.latitude, position.coords.longitude]);
            showMap();
            map.setView(coordinates(), 17);
            status.textContent += ` Estimated accuracy: ${Math.round(position.coords.accuracy)} m. Move the pin if needed.`;
        }, error => {
            if (request !== locationRequest) return;
            cancelLocationRequest();
            clear.disabled = latitude.value === '' && longitude.value === '';
            status.textContent = ({
                1: 'Location access was denied. Choose a point on the map or enter coordinates.',
                2: 'Your location could not be determined. Choose a point on the map or try again.',
                3: 'Finding your location timed out. Try again or choose a point on the map.',
            })[error.code] ?? 'Your location is unavailable. Choose a point on the map or enter coordinates.';
        }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 0});
    });

    clear.addEventListener('click', () => {
        cancelLocationRequest();
        latitude.value = '';
        longitude.value = '';
        updateMarker();
    });

    [latitude, longitude].forEach(input => input.addEventListener('input', () => {
        cancelLocationRequest();
        updateMarker();
        if (map && coordinates()) map.panTo(coordinates());
    }));

    dialog?.addEventListener('close', () => {
        cancelLocationRequest();
        updateMarker();
    });

    new ResizeObserver(() => {
        if (map && !mapElement.hidden && mapElement.clientWidth > 0) map.invalidateSize();
    }).observe(mapElement);

    updateMarker();
}
