import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {parse} from '@vue/compiler-sfc';
import {markRaw, reactive, isProxy} from 'vue';
import * as presentation from '../../../resources/js/support/qso-map/presentation.mjs';
import {EMPTY_FEATURE_COLLECTION, selectedPath} from '../../../resources/js/support/qso-map/geometry.mjs';
import {EMPTY_HIGHLIGHT_FILTER} from '../../../resources/js/support/qso-map/style.mjs';
import {createMapLifecycle} from '../../../resources/js/support/qso-map/mapLifecycle.mjs';
import {createGlobeRotation} from '../../../resources/js/support/qso-map/globeRotation.mjs';
import {createSelection} from '../../../resources/js/support/qso-map/selection.mjs';
import {createRequests} from '../../../resources/js/support/qso-map/requests.mjs';
import {Events, FakeMap, deferred, feature, timers} from './fakes.mjs';

const source = await readFile(new URL('../../../resources/js/components/QSOMapComponent.vue', import.meta.url), 'utf8');
const {descriptor, errors} = parse(source);
assert.deepEqual(errors, []);

class FakePopup {
    setLngLat(coordinates) { this.coordinates = coordinates; return this; }
    setHTML(html) { this.html = html; return this; }
    addTo(map) { map.record('popup', this); return this; }
    remove() { this.removed = true; }
}

// Evaluate the real Options API script, replacing only browser/vendor imports.
// This keeps lifecycle/computed/method wiring under test without WebGL or a DOM.
const dependencies = {
    ...presentation, markRaw, Map: FakeMap, Popup: FakePopup,
    setWorkerUrl() {}, maplibreWorkerUrl: '/worker.js',
    isMapboxURL: url => url.startsWith('mapbox://'),
    transformMapboxUrl: (url, resourceType, key) => ({url, resourceType, key}),
    RelativeUTCTime: {format: value => value ? `Imported ${value}` : 'Import status unknown'},
    EMPTY_FEATURE_COLLECTION, selectedPath, EMPTY_HIGHLIGHT_FILTER,
    createMapLifecycle, createGlobeRotation, createSelection, createRequests,
};
const script = descriptor.script.content.replace(/import\s+[^;]+;/g, '').replace('export default', 'return');
const options = new Function(...Object.keys(dependencies), script)(...Object.values(dependencies));

function fixture(t, {autoImages = true} = {}) {
    const browser = new Events();
    const clock = timers();
    const frames = new Map();
    let frameId = 0;
    const images = [], requests = [], scrolls = [];
    browser.matchMedia = () => ({matches: false});
    browser.setTimeout = clock.setTimer;
    browser.clearTimeout = clock.clearTimer;
    const globals = {
        window: browser,
        document: {
            hidden: false,
            getElementById: id => ({scrollIntoView: settings => scrolls.push([id, settings])}),
        },
        Image: class {
            constructor() { images.push(this); }
            set src(url) {
                this.url = url;
                if (autoImages) queueMicrotask(() => this.onload?.());
            }
        },
        requestAnimationFrame(callback) { frames.set(++frameId, callback); return frameId; },
        cancelAnimationFrame(frame) { frames.delete(frame); },
        axios: {
            get(url, settings) {
                const request = {...deferred(), url, ...settings};
                requests.push(request);
                return request.promise;
            },
        },
    };
    const originals = new Map();
    for (const [key, value] of Object.entries(globals)) {
        const original = Object.getOwnPropertyDescriptor(globalThis, key);
        originals.set(key, original);
        Object.defineProperty(globalThis, key, {value, configurable: true, writable: true});
    }
    const component = reactive({
        ...options.data(),
        mapboxKey: 'test-key', config: {lng: -75, lat: 40, zoom: 2.5},
        qth: {lng: -75, lat: 40},
        $el: markRaw(new Events()), $refs: {mapContainer: {}},
        $nextTick: callback => Promise.resolve().then(callback),
    });
    for (const [key, method] of Object.entries(options.methods)) component[key] = method.bind(component);
    for (const [key, computed] of Object.entries(options.computed)) {
        Object.defineProperty(component, key, {get: () => computed.call(component), configurable: true});
    }
    options.mounted.call(component);
    t.after(() => {
        try {
            if (!component.disposed) options.beforeUnmount.call(component);
        } finally {
            for (const [key, original] of originals) {
                if (original) Object.defineProperty(globalThis, key, original);
                else delete globalThis[key];
            }
        }
    });
    return {component, map: component.mapObject, browser, clock, frames, images, requests, scrolls};
}

test('component coordinates map setup, raw instances, filtering, hover, selection and result updates', async t => {
    const f = fixture(t);
    const {component, map} = f;
    assert.equal(isProxy(map), false);
    assert.equal(isProxy(component.selection), false);
    assert.equal(map.options.container, component.$refs.mapContainer);
    assert.equal(map.options.zoom, 2.5);
    assert.deepEqual(map.options.transformRequest('/local', 'Image'), {url: '/local'});
    assert.equal(map.options.transformRequest('mapbox://style', 'Style').key, 'test-key');
    await map.emit('load');
    assert.equal(component.loaded, true);
    assert.equal(map.sources.size, 3);
    assert.equal(map.layers.size, 7);
    assert.equal(map.images.size, 5);
    assert.equal(f.frames.size, 1);
    assert.deepEqual(f.requests.map(request => request.url), [
        '/api/radio/bands', '/api/radio/modes', '/api/radio/qsos/band/All/mode/All?limit=200&sort=newest',
    ]);
    f.requests[0].resolve({status: 200, data: ['', '20M']});
    f.requests[1].resolve({status: 200, data: [null, 'FT8']});
    const contacts = {type: 'FeatureCollection', features: [feature(1), feature(2, [-70, 30])],
        meta: {total: 10, returned: 2, limit: 200, last_imported_at: 'date'}};
    f.requests[2].resolve({status: 200, data: contacts});
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(component.bandOptions, ['All', '20M']);
    assert.deepEqual(component.modeOptions, ['All', 'FT8']);
    assert.equal(component.resultSummary, 'Showing 2 of 10 contacts');
    assert.equal(component.loadingQsos, false);
    assert.equal(component.lastImportSummary, 'Imported date');
    assert.deepEqual(map.getSource('qsos').data.features, contacts.features);
    component.selectContactFromTable(component.tableContacts[0]);
    await Promise.resolve();
    assert.equal(component.selectedContactId, '1');
    assert.equal(f.scrolls[0][0], 'qso-map-shell');
    assert.equal(map.getSource('selected-qso-path').data.features.length, 1);
    component.highlightFeature(component.tableContacts[1]);
    assert.equal(map.calls.at(-1)[2][2], '2');
    component.clearHighlight();
    assert.equal(map.calls.at(-1)[2][2], '1');
    f.clock.run(875);
    f.clock.run(1525);
    assert.equal(map.calls.at(-1)[0], 'popup');
    map.rendered = [{...feature(2), layer: {id: 'qso-badge'}}, {...feature(1), layer: {id: 'qsos'}}];
    await map.emit('click', {point: {}, lngLat: {lng: -74}});
    assert.equal(component.selectedContactId, '1');
    await map.emit('mousemove', {point: {}});
    assert.equal(map.canvas.style.cursor, 'pointer');
    await map.emit('mouseout');
    assert.equal(map.canvas.style.cursor, '');
    map.rendered = [];
    await map.emit('click', {point: {}});
    assert.equal(component.selectedContactId, null);
    assert.equal(f.clock.callbacks.size, 0);
    assert.equal(map.getSource('selected-qso-path').data.features.length, 0);
    component.currentBand = '20M';
    component.currentMode = 'SSB / PHONE';
    component.searchTerm = 'K1 A&B';
    component.currentSort = 'distance_desc';
    assert.equal(component.apiUrl, '/api/radio/qsos/band/20M/mode/SSB%20%2F%20PHONE?limit=200&search=K1+A%26B&sort=distance_desc');
    assert.equal(component.currentSortLabel, 'Longest distance (DX)');
    assert.equal(component.hasActiveFilters, true);
    component.clearFilters();
    assert.equal(component.hasActiveFilters, false);
    component.qth = {lng: -74, lat: 42};
    component.selectContactFromTable(feature(1));
    assert.equal(map.calls.at(-1)[0], 'flyTo');
    assert.equal(map.calls.at(-1)[1].duration, 650);
    options.beforeUnmount.call(component);
    assert.equal(map.removed, true);
    assert.equal(map.listenerCount(), 0);
    assert.equal(f.frames.size, 0);
    assert.equal(f.browser.listenerCount() + component.$el.listenerCount(), 0);
});

test('unmount during icon initialization prevents setup and every pending AJAX callback', async t => {
    const f = fixture(t, {autoImages: false});
    const loading = f.map.emit('load');
    assert.equal(f.images.length, 5);
    const callbacks = f.images.map(image => image.onload);
    options.beforeUnmount.call(f.component);
    callbacks.forEach(callback => callback());
    f.requests.forEach(request => request.resolve({status: 200, data: ['unexpected']}));
    await loading;
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(f.component.loaded, false);
    assert.deepEqual(f.component.bands, []);
    assert.deepEqual(f.component.modes, []);
    assert.equal(f.map.sources.size, 0);
    assert.equal(f.map.layers.size, 0);
    assert.equal(f.map.listenerCount(), 0);
    assert.equal(f.clock.callbacks.size, 0);
    assert.equal(f.frames.size, 0);
});

test('search debounce invalidates old responses; filtering removes stale selection/hover and popup timers', async t => {
    const f = fixture(t);
    await f.map.emit('load');
    f.component.selectContact(feature(1), {animate: true});
    f.component.highlightFeature(feature(1));
    f.component.searchTerm = 'new';
    options.watch.searchTerm.call(f.component);
    assert.equal(f.requests[2].signal.aborted, true);
    f.requests[2].resolve({status: 200, data: {features: [feature(99)]}});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(f.component.qsoCount, 0);
    // A band/sort change during debounce loads immediately and cancels its timer.
    options.watch.currentBand.call(f.component);
    assert.ok(f.requests[3].url.includes('search=new'));
    f.requests[3].resolve({status: 200, data: {features: [feature(2)]}});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(f.component.selectedContactId, null);
    assert.equal(f.component.hoveredContactId, null);
    assert.equal(f.component.qsoCount, 1);
    assert.equal(f.component.resultMeta.total, 1);
    assert.equal(f.component.resultSummary, '1 contacts');
    assert.equal(f.clock.callbacks.size, 0);
    await new Promise(resolve => setTimeout(resolve, 275));
    assert.equal(f.requests.length, 4);
});
