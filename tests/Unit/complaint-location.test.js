import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../resources/js/complaint-location.js', import.meta.url), 'utf8').replace(/^import .*;\n/gm, '');

function createPicker({secure = true} = {}) {
    const fields = new Map();
    const element = name => {
        if (!fields.has(name)) fields.set(name, {
            value: '', hidden: true, disabled: false, textContent: '', required: false,
            listeners: {}, attributes: {},
            addEventListener(event, listener) { this.listeners[event] = listener; },
            setAttribute(key, value) { this.attributes[key] = value; },
            removeAttribute(key) { delete this.attributes[key]; },
        });
        return fields.get(name);
    };
    const picker = {querySelector: selector => element(selector), closest: () => element('dialog')};
    const requests = [];
    const map = {
        setView() { return this; }, on() { return this; }, invalidateSize() {}, panTo() {},
    };
    const leaflet = {
        latLng(point) { return {wrap: () => Array.isArray(point) ? {lat: point[0], lng: point[1]} : point}; },
        map: () => map,
        tileLayer: () => ({addTo() { return this; }, on() { return this; }}),
        icon: () => ({}),
        marker: () => ({addTo() { return this; }, on() { return this; }, setLatLng() {}, remove() {}}),
    };
    runInNewContext(source, {
        document: {querySelector: () => picker},
        navigator: {geolocation: {getCurrentPosition(success, error, options) { requests.push({success, error, options}); }}},
        window: {isSecureContext: secure},
        L: leaflet, markerIcon: '', markerIconRetina: '', markerShadow: '',
        ResizeObserver: class { observe() {} },
    });
    return {element, requests, click: name => element(`[data-location-${name}]`).listeners.click()};
}

test('location is requested only on user action and successful coordinates are selected', () => {
    const ui = createPicker();
    assert.equal(ui.requests.length, 0);
    ui.click('locate');
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.element('[data-location-locate]').disabled, true);
    assert.equal(ui.requests[0].options.timeout, 15000);
    ui.requests[0].success({coords: {latitude: 14.4297, longitude: 120.9367, accuracy: 12}});
    assert.equal(ui.element('[data-location-latitude]').value, '14.4297000');
    assert.equal(ui.element('[data-location-longitude]').value, '120.9367000');
    assert.equal(ui.element('[data-location-map]').hidden, false);
    assert.equal(ui.element('[data-location-locate]').disabled, false);
    assert.match(ui.element('[data-location-status]').textContent, /accuracy: 12 m/);
});

for (const [code, message] of [[1, /denied/], [2, /could not be determined/], [3, /timed out/]]) {
    test(`geolocation error ${code} restores controls and offers a fallback`, () => {
        const ui = createPicker();
        ui.click('locate');
        ui.requests[0].error({code});
        assert.equal(ui.element('[data-location-locate]').disabled, false);
        assert.equal(ui.element('[data-location-latitude]').value, '');
        assert.match(ui.element('[data-location-status]').textContent, message);
        assert.match(ui.element('[data-location-status]').textContent, /map/);
    });
}

for (const action of ['clear', 'close', 'manual']) {
    test(`late geolocation results cannot overwrite a ${action} action`, () => {
        const ui = createPicker();
        ui.click('locate');
        if (action === 'close') ui.element('dialog').listeners.close();
        else if (action === 'clear') ui.click('clear');
        else {
            ui.element('[data-location-latitude]').value = '0';
            ui.element('[data-location-longitude]').value = '0';
            ui.element('[data-location-latitude]').listeners.input();
        }
        ui.requests[0].success({coords: {latitude: 14, longitude: 120, accuracy: 5}});
        assert.equal(ui.element('[data-location-latitude]').value, action === 'manual' ? '0' : '');
        assert.equal(ui.element('[data-location-longitude]').value, action === 'manual' ? '0' : '');
        assert.equal(ui.element('[data-location-map]').hidden, true);
    });
}

test('insecure contexts do not attempt geolocation', () => {
    const ui = createPicker({secure: false});
    ui.click('locate');
    assert.equal(ui.requests.length, 0);
    assert.match(ui.element('[data-location-status]').textContent, /HTTPS/);
});
