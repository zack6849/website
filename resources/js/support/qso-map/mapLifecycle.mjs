import {EMPTY_FEATURE_COLLECTION, qthFeatureCollection} from './geometry.mjs';
import {createMapIcons} from './icons.mjs';
import {contactLayers, globeStyle, QSO_LAYERS} from './style.mjs';

export function createMapLifecycle({map, qth, onReady, onSelect, onDeselect, onError = console.error}) {
    const icons = createMapIcons({map, onError});
    const listeners = [];
    let disposed = false;
    function listen(event, callback) {
        map.on(event, callback);
        listeners.push([event, callback]);
    }
    listen('error', event => onError(event.error ?? event));
    listen('styleimagemissing', event => { icons.register(event.id); });
    listen('load', async () => {
        try {
            await icons.registerAll();
            // The load event can finish its asynchronous icon work after unmount.
            if (disposed) return;
            for (const [id, data] of [
                ['qsos', EMPTY_FEATURE_COLLECTION],
                ['qth', qthFeatureCollection(qth)],
                ['selected-qso-path', EMPTY_FEATURE_COLLECTION],
            ]) {
                map.addSource(id, {type: 'geojson', data});
            }
            for (const layer of contactLayers()) map.addLayer(layer);
            listen('click', event => {
                const features = map.queryRenderedFeatures(event.point, {layers: QSO_LAYERS});
                if (features.length === 0) {
                    onDeselect();
                } else {
                    onSelect(features.find(feature => feature.layer.id === 'qsos') ?? features[0], {
                        scrollRow: true, popupLngLat: event.lngLat, animate: true,
                    });
                }
            });
            listen('mousemove', event => {
                const features = map.queryRenderedFeatures(event.point, {layers: QSO_LAYERS});
                map.getCanvas().style.cursor = features.length > 0 ? 'pointer' : '';
            });
            listen('mouseout', () => { map.getCanvas().style.cursor = ''; });
            onReady();
        } catch (error) {
            if (!disposed) onError(error);
        }
    });
    map.setStyle('mapbox://styles/mapbox/outdoors-v12', {transformStyle: globeStyle});
    return {
        dispose() {
            disposed = true;
            icons.dispose();
            for (const [event, callback] of listeners) map.off(event, callback);
            map.remove();
        },
    };
}
