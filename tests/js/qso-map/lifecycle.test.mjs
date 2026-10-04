import test from 'node:test';
import assert from 'node:assert/strict';
import {createGlobeRotation} from '../../../resources/js/support/qso-map/globeRotation.mjs';
import {createSelection} from '../../../resources/js/support/qso-map/selection.mjs';
import {createMapIcons} from '../../../resources/js/support/qso-map/icons.mjs';
import {createRequests} from '../../../resources/js/support/qso-map/requests.mjs';
import {Events, FakeMap, feature, timers, deferred} from './fakes.mjs';

function rotationFixture() {
    const root = new Events();
    const browser = new Events();
    const page = {hidden: false};
    const motion = {matches: false};
    browser.matchMedia = () => motion;
    const map = new FakeMap();
    let selected = false;
    let now = 0;
    let id = 0;
    const frames = new Map();
    const rotation = createGlobeRotation({
        map, root, hasSelection: () => selected, window: browser, document: page, now: () => now,
        requestFrame(callback) { frames.set(++id, callback); return id; },
        cancelFrame(frame) { frames.delete(frame); },
    });
    return {
        map, root, browser, page, motion, rotation, frames,
        select(value) { selected = value; },
        time(value) { now = value; },
        tick(value) {
            now = value;
            const callbacks = [...frames.values()];
            frames.clear();
            callbacks.forEach(callback => callback(value));
        },
    };
}

test('spin starts immediately, runs at 2 degrees/sec, wraps longitude, caps frame delta, and disposes', () => {
    const f = rotationFixture();
    f.rotation.start();
    f.rotation.start();
    assert.equal(f.frames.size, 1);
    f.tick(0);
    f.tick(100);
    assert.ok(Math.abs(f.map.center.lng - (-179.9)) < 1e-9);
    assert.equal(f.map.center.lat, 40);
    f.tick(5000);
    assert.ok(Math.abs(f.map.center.lng - (-179.7)) < 1e-9);
    const queued = [...f.frames.values()][0];
    const count = f.map.calls.length;
    f.rotation.dispose();
    queued(5100);
    f.rotation.start();
    assert.equal(f.frames.size, 0);
    assert.equal(f.root.listenerCount() + f.browser.listenerCount(), 0);
    assert.equal(f.map.calls.length, count);
});

test('spin gates zoom, selection, map movement, hidden tabs, and reduced motion', () => {
    const f = rotationFixture();
    f.rotation.start();
    f.tick(0);
    let time = 0;
    for (const [disable, enable] of [
        [() => { f.map.zoom = 2.51; }, () => { f.map.zoom = 2.5; }],
        [() => f.select(true), () => f.select(false)],
        [() => { f.map.moving = true; }, () => { f.map.moving = false; }],
        [() => { f.page.hidden = true; }, () => { f.page.hidden = false; }],
        [() => { f.motion.matches = true; }, () => { f.motion.matches = false; }],
    ]) {
        const count = f.map.calls.length;
        disable();
        f.tick(time += 100);
        assert.equal(f.map.calls.length, count);
        enable();
        f.tick(time += 100);
        assert.equal(f.map.calls.length, count + 1);
    }
    f.rotation.dispose();
});

test('interaction pauses for 5 seconds and held pointers cannot resume rotation', async () => {
    const f = rotationFixture();
    f.rotation.start();
    f.tick(0);
    for (const event of ['wheel', 'keydown']) {
        f.time(100);
        await f.root.emit(event);
        const count = f.map.calls.length;
        f.tick(5099);
        assert.equal(f.map.calls.length, count);
        f.tick(5100);
        assert.equal(f.map.calls.length, count + 1);
    }
    f.time(20000);
    await f.root.emit('pointerdown', {pointerId: 1});
    await f.root.emit('pointerdown', {pointerId: 2});
    const count = f.map.calls.length;
    f.tick(40000);
    assert.equal(f.map.calls.length, count);
    await f.browser.emit('pointerup', {pointerId: 1});
    f.tick(60000);
    assert.equal(f.map.calls.length, count);
    await f.browser.emit('pointercancel', {pointerId: 2});
    f.tick(64999);
    assert.equal(f.map.calls.length, count);
    f.tick(65000);
    assert.equal(f.map.calls.length, count + 1);
    await f.root.emit('pointerdown', {pointerId: 3});
    await f.browser.emit('blur');
    f.tick(90000);
    assert.equal(f.map.calls.length, count + 2);
    f.rotation.dispose();
});

function selectionFixture(qth = {lng: -75, lat: 40}) {
    const clock = timers();
    const map = new FakeMap();
    const popups = [];
    const selection = createSelection({
        map, qth, ...clock,
        createPopup() {
            const popup = {
                removed: false,
                setLngLat(coordinates) { this.coordinates = coordinates; return this; },
                setHTML(html) { this.html = html; return this; },
                addTo(map) { this.map = map; popups.push(this); return this; },
                remove() { this.removed = true; },
            };
            return popup;
        },
    });
    return {clock, map, popups, selection};
}

test('selection preserves fit/ease/popup timing and cancels stale callbacks on replacement and disposal', () => {
    const f = selectionFixture();
    f.selection.focus(feature(1), {animate: true});
    assert.equal(f.map.calls[0][0], 'fitBounds');
    assert.equal(f.map.calls[0][2].duration, 850);
    const stale = [...f.clock.callbacks.values()].map(entry => entry.callback);
    f.clock.run(875);
    assert.equal(f.map.calls.at(-1)[0], 'easeTo');
    assert.equal(f.map.calls.at(-1)[1].duration, 700);
    f.clock.run(1525);
    assert.deepEqual(f.popups[0].coordinates, [-74, 42]);
    f.selection.focus(feature(2, [-70, 41]), {animate: true});
    assert.equal(f.popups[0].removed, true);
    f.selection.focus(feature(3, [-60, 40]));
    stale.forEach(callback => callback());
    assert.equal(f.popups.length, 2);
    assert.deepEqual(f.popups.at(-1).coordinates, [-60, 40]);
    assert.ok(f.map.calls.some(call => call[0] === 'stop'));
    f.selection.focus(feature(4), {animate: true});
    const afterDisposal = [...f.clock.callbacks.values()].map(entry => entry.callback);
    f.selection.dispose();
    f.map.remove();
    afterDisposal.forEach(callback => callback());
    f.selection.focus(feature(5));
    assert.equal(f.clock.callbacks.size, 0);
    assert.equal(f.popups.length, 2);
});

test('same-position, missing-QTH, world-copy and invalid selections preserve fallback behavior and cancellation', () => {
    const f = selectionFixture();
    f.selection.focus(feature(1, [-75, 40]), {animate: true});
    assert.equal(f.map.calls[0][0], 'flyTo');
    assert.equal(f.map.calls[0][1].duration, 650);
    f.clock.run(675);
    assert.equal(f.popups.length, 1);
    f.selection.focus(feature(2), {animate: true});
    f.selection.focus(feature(3, null));
    assert.equal(f.clock.callbacks.size, 0);
    assert.equal(f.popups[0].removed, true);
    f.selection.dispose();
    const missingHome = selectionFixture({lat: 'bad'});
    missingHome.selection.focus(feature(1, [-179, 30]), {animate: true, fly: true, popupLngLat: {lng: 179}});
    assert.equal(missingHome.map.calls[0][0], 'flyTo');
    assert.equal(missingHome.map.calls[0][1].zoom, 5);
    assert.deepEqual(missingHome.popups[0].coordinates, [181, 30]);
    assert.equal(missingHome.clock.callbacks.size, 0);
    missingHome.selection.dispose();
});

test('icons deduplicate pending loads, accept late images, and detach callbacks on disposal', async () => {
    const map = new FakeMap();
    const clock = timers();
    const images = [];
    const icons = createMapIcons({
        map, ...clock, createImage() { const image = {}; images.push(image); return image; },
    });
    const first = icons.register('mode-phone');
    assert.equal(icons.register('mode-phone'), first);
    assert.equal(images.length, 1);
    clock.run(1000);
    await first;
    assert.equal(map.images.size, 0);
    images[0].onload();
    assert.equal(map.images.has('mode-phone'), true);
    const second = icons.register('mode-other');
    const callback = images[1].onload;
    icons.dispose();
    await second;
    assert.equal(images[1].onload, null);
    map.remove();
    callback();
    await icons.register('qth-antenna');
    assert.equal(clock.callbacks.size, 0);
    assert.equal(images.length, 2);
});

test('icon errors settle registration and unknown/preloaded style images do not reload', async () => {
    const map = new FakeMap();
    map.images.add('mode-phone');
    const clock = timers();
    const errors = [];
    let image;
    const icons = createMapIcons({
        map, ...clock, createImage() { image = {}; return image; }, onError: error => errors.push(error),
    });
    await icons.register('mode-phone');
    await icons.register('unknown');
    assert.equal(image, undefined);
    const load = icons.register('mode-digital');
    image.onerror('failed');
    await load;
    assert.deepEqual(errors, ['failed']);
    assert.equal(clock.callbacks.size, 0);
    icons.dispose();
});

test('requests ignore stale success/error/finally, invalidate debounce responses and abort on disposal', async () => {
    const calls = [];
    const accepted = [];
    const settled = [];
    const errors = [];
    const requests = createRequests({
        get(url, options) {
            const result = deferred();
            calls.push({url, ...options, ...result});
            return result.promise;
        },
        onError: error => errors.push(error),
    });
    const load = key => requests.load(key, key, data => accepted.push(data), () => settled.push(key));
    const old = load('qsos');
    const latest = load('qsos');
    assert.equal(calls[0].signal.aborted, true);
    calls[1].resolve({status: 200, data: 'latest'});
    await latest;
    calls[0].resolve({status: 200, data: 'old'});
    await old;
    assert.deepEqual(accepted, ['latest']);
    assert.deepEqual(settled, ['qsos']);
    const invalidated = load('qsos');
    requests.invalidate('qsos');
    calls[2].resolve({status: 200, data: 'debounced'});
    await invalidated;
    assert.deepEqual(accepted, ['latest']);
    const staleError = load('qsos');
    const pending = load('qsos');
    const bands = load('bands');
    requests.dispose();
    assert.equal(calls[4].signal.aborted, true);
    assert.equal(calls[5].signal.aborted, true);
    calls[3].reject(new Error('stale'));
    calls[4].resolve({status: 200, data: 'unmounted'});
    calls[5].resolve({status: 200, data: ['40M']});
    await Promise.all([staleError, pending, bands]);
    await load('modes');
    assert.equal(calls.length, 6);
    assert.deepEqual(errors, []);
    assert.deepEqual(accepted, ['latest']);
    assert.deepEqual(settled, ['qsos']);
});

test('current request errors are reported and loading settles', async () => {
    const errors = [], settled = [];
    const error = new Error('network');
    const requests = createRequests({
        get: () => Promise.reject(error), onError: value => errors.push(value),
    });
    await requests.load('qsos', '/api', () => assert.fail(), () => settled.push(true));
    assert.deepEqual(errors, [error]);
    assert.deepEqual(settled, [true]);
    requests.dispose();
});
