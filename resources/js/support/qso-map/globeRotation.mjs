const IDLE_MS = 5000;
const MAX_ZOOM = 2.5;
const DEGREES_PER_SECOND = 2;

export function createGlobeRotation({
    map, root, hasSelection,
    document: page = document,
    window: browser = window,
    now = () => performance.now(),
    requestFrame = callback => requestAnimationFrame(callback),
    cancelFrame = frame => cancelAnimationFrame(frame),
}) {
    const reducedMotion = browser.matchMedia('(prefers-reduced-motion: reduce)');
    const pointers = new Set();
    let frame = null;
    let lastFrame = null;
    let lastInteraction = -IDLE_MS;
    let disposed = false;
    const pause = () => { lastInteraction = now(); };
    const pointerDown = event => { pointers.add(event.pointerId); pause(); };
    const pointerUp = event => {
        if (pointers.delete(event.pointerId)) pause();
    };
    const blur = () => { pointers.clear(); pause(); };
    const listeners = [
        [root, 'pointerdown', pointerDown],
        [root, 'wheel', pause],
        [root, 'keydown', pause],
        [browser, 'pointerup', pointerUp],
        [browser, 'pointercancel', pointerUp],
        [browser, 'blur', blur],
    ];
    for (const [target, event, callback] of listeners) {
        target.addEventListener(event, callback, {capture: true, passive: true});
    }
    const rotate = timestamp => {
        if (disposed) return;
        const elapsed = lastFrame === null ? 0 : Math.min(timestamp - lastFrame, 100);
        lastFrame = timestamp;
        if (!hasSelection() && pointers.size === 0 && map.getZoom() <= MAX_ZOOM
            && !map.isMoving() && !page.hidden && !reducedMotion.matches
            && timestamp - lastInteraction >= IDLE_MS) {
            const center = map.getCenter();
            const longitude = center.lng + DEGREES_PER_SECOND * elapsed / 1000;
            map.jumpTo({center: [((longitude + 180) % 360 + 360) % 360 - 180, center.lat]});
        }
        frame = requestFrame(rotate);
    };
    return {
        pause,
        start() {
            if (!disposed && frame === null) frame = requestFrame(rotate);
        },
        dispose() {
            disposed = true;
            cancelFrame(frame);
            for (const [target, event, callback] of listeners) {
                target.removeEventListener(event, callback, true);
            }
            pointers.clear();
        },
    };
}
