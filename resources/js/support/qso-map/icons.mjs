import {MODE_ICON_URLS, QTH_ICON_URL} from './presentation.mjs';

export function createMapIcons({
    map, createImage = () => new Image(128, 128),
    setTimer = (callback, delay) => window.setTimeout(callback, delay),
    clearTimer = timer => window.clearTimeout(timer),
    onError = error => console.error(error),
}) {
    const urls = {...MODE_ICON_URLS, 'qth-antenna': QTH_ICON_URL};
    const pending = new Map();
    let disposed = false;

    function register(name) {
        if (disposed || !urls[name] || map.hasImage(name)) return Promise.resolve();
        if (pending.has(name)) return pending.get(name).promise;
        const image = createImage();
        let resolve;
        const promise = new Promise(done => { resolve = done; });
        let timer;
        const finish = () => {
            clearTimer(timer);
            image.onload = null;
            image.onerror = null;
            pending.delete(name);
            resolve();
        };
        pending.set(name, {promise, finish});
        // Let map setup proceed after a second, but still accept a late image
        // while the map is alive. Disposal detaches these handlers.
        timer = setTimer(resolve, 1000);
        image.onload = () => {
            try {
                if (!disposed && !map.hasImage(name)) map.addImage(name, image, {pixelRatio: 2});
            } catch (error) {
                onError(error);
            } finally {
                finish();
            }
        };
        image.onerror = error => { onError(error); finish(); };
        image.src = urls[name];
        return promise;
    }
    return {
        register,
        registerAll: () => Promise.all(Object.keys(urls).map(register)),
        dispose() {
            disposed = true;
            for (const {finish} of pending.values()) finish();
        },
    };
}
