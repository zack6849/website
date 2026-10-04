export function timers() {
    let id = 0;
    const callbacks = new Map();
    return {
        callbacks,
        setTimer(callback, delay) {
            callbacks.set(++id, {callback, delay});
            return id;
        },
        clearTimer(timer) { callbacks.delete(timer); },
        run(delay) {
            for (const [timer, entry] of [...callbacks]) {
                if (entry.delay === delay) {
                    callbacks.delete(timer);
                    entry.callback();
                }
            }
        },
    };
}

export class Events {
    listeners = new Map();
    addEventListener(event, callback) {
        if (!this.listeners.has(event)) this.listeners.set(event, new Set());
        this.listeners.get(event).add(callback);
    }
    removeEventListener(event, callback) { this.listeners.get(event)?.delete(callback); }
    emit(event, value = {}) {
        return Promise.all([...this.listeners.get(event) ?? []].map(callback => callback(value)));
    }
    listenerCount() {
        return [...this.listeners.values()].reduce((count, listeners) => count + listeners.size, 0);
    }
}

export class FakeMap extends Events {
    calls = [];
    sources = new Map();
    layers = new Map();
    images = new Set();
    zoom = 2.5;
    moving = false;
    center = {lng: 179.9, lat: 40};
    canvas = {style: {}};
    rendered = [];
    removed = false;
    constructor(options) { super(); this.options = options; }
    record(name, ...args) {
        if (this.removed) throw new Error(`Map used after removal: ${name}`);
        this.calls.push([name, ...args]);
    }
    on(event, callback) { this.addEventListener(event, callback); }
    off(event, callback) { this.removeEventListener(event, callback); }
    setStyle(...args) { this.record('setStyle', ...args); }
    addSource(id, source) {
        this.record('addSource', id, source);
        this.sources.set(id, {...source, setData(data) { this.data = data; }});
    }
    getSource(id) { return this.sources.get(id); }
    addLayer(layer) { this.record('addLayer', layer); this.layers.set(layer.id, layer); }
    getLayer(id) { return this.layers.get(id); }
    setFilter(id, filter) { this.record('setFilter', id, filter); }
    hasImage(name) { this.record('hasImage', name); return this.images.has(name); }
    addImage(name, image, options) { this.record('addImage', name, image, options); this.images.add(name); }
    getZoom() { return this.zoom; }
    getCenter() { return this.center; }
    isMoving() { return this.moving; }
    jumpTo(options) {
        this.record('jumpTo', options);
        this.center = {lng: options.center[0], lat: options.center[1]};
    }
    flyTo(...args) { this.record('flyTo', ...args); }
    fitBounds(...args) { this.record('fitBounds', ...args); }
    easeTo(...args) { this.record('easeTo', ...args); }
    stop() { this.record('stop'); }
    getCanvas() { return this.canvas; }
    queryRenderedFeatures() { return this.rendered; }
    remove() { this.record('remove'); this.removed = true; }
}

export function feature(id = 1, coordinates = [-74, 42], properties = {}) {
    return {type: 'Feature', geometry: {type: 'Point', coordinates}, properties: {id, ...properties}};
}

export function deferred() {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return {promise, resolve, reject};
}
