import {isCoordinatePair, popupCoordinates, qthCoordinates, selectionBounds} from './geometry.mjs';
import {buildPopupDescription} from './presentation.mjs';

export function createSelection({
    map, qth, getQth = () => qth, createPopup,
    setTimer = (callback, delay) => window.setTimeout(callback, delay),
    clearTimer = timer => window.clearTimeout(timer),
}) {
    const timers = new Set();
    let generation = 0;
    let popup = null;
    let disposed = false;
    let animating = false;

    function clear() {
        generation += 1;
        for (const timer of timers) clearTimer(timer);
        timers.clear();
        if (animating) map.stop();
        animating = false;
        popup?.remove();
        popup = null;
    }
    function queue(callback, delay) {
        const current = generation;
        const timer = setTimer(() => {
            timers.delete(timer);
            if (!disposed && current === generation) callback();
        }, delay);
        timers.add(timer);
    }
    function openPopup(feature, coordinates) {
        if (disposed) return;
        animating = false;
        popup?.remove();
        popup = createPopup()
            .setLngLat(coordinates)
            .setHTML(buildPopupDescription(feature.properties))
            .addTo(map);
    }
    return {
        clear,
        focus(feature, options = {}) {
            if (disposed) return;
            // Even a non-animated/invalid replacement must cancel the previous selection.
            clear();
            if (!isCoordinatePair(feature?.geometry?.coordinates)) return;
            const coordinates = popupCoordinates(feature, options.popupLngLat);
            const home = qthCoordinates(getQth());
            if (!options.animate || home === null) {
                if (options.fly) {
                    map.flyTo({center: coordinates, zoom: Math.max(map.getZoom(), 5), essential: true});
                }
                openPopup(feature, coordinates);
                return;
            }
            animating = true;
            if (Number(home[0]) === Number(coordinates[0]) && Number(home[1]) === Number(coordinates[1])) {
                map.flyTo({
                    center: coordinates, zoom: Math.max(map.getZoom(), 5.75),
                    duration: 650, essential: true,
                });
                queue(() => openPopup(feature, coordinates), 675);
                return;
            }
            map.fitBounds(selectionBounds(home, coordinates), {
                padding: {top: 96, right: 96, bottom: 96, left: 96},
                maxZoom: 4.75, duration: 850, essential: true,
            });
            queue(() => map.easeTo({
                center: coordinates, zoom: Math.max(map.getZoom(), 5.75),
                duration: 700, essential: true,
            }), 875);
            queue(() => openPopup(feature, coordinates), 1525);
        },
        dispose() {
            disposed = true;
            clear();
        },
    };
}
