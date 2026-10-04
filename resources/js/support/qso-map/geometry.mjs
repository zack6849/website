export const EMPTY_FEATURE_COLLECTION = {type: 'FeatureCollection', features: []};
const ARC_POINT_COUNT = 48;

export function isCoordinatePair(value) {
    return Array.isArray(value)
        && value.length >= 2
        && Number.isFinite(Number(value[0]))
        && Number.isFinite(Number(value[1]));
}

export function qthCoordinates(qth) {
    const lat = Number(qth?.lat);
    const lng = Number(qth?.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)
        || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        return null;
    }

    return [lng, lat];
}

export function qthFeatureCollection(qth) {
    const coordinates = qthCoordinates(qth);
    return coordinates === null ? EMPTY_FEATURE_COLLECTION : {
        type: 'FeatureCollection',
        features: [{
            type: 'Feature',
            geometry: {type: 'Point', coordinates},
            properties: {label: qth?.label ?? 'Approx. QTH'},
        }],
    };
}

export function unwrapLongitude(originLng, targetLng) {
    let lng = Number(targetLng);
    if (!Number.isFinite(lng) || !Number.isFinite(originLng)) {
        return lng;
    }
    while (Math.abs(lng - originLng) > 180) {
        lng += lng > originLng ? -360 : 360;
    }
    return lng;
}

export function generateArcCoordinates(start, end) {
    const startLng = Number(start[0]);
    const startLat = Number(start[1]);
    const endLng = unwrapLongitude(startLng, end[0]);
    const endLat = Number(end[1]);
    const dx = endLng - startLng;
    const dy = endLat - startLat;
    const distance = Math.sqrt(dx * dx + dy * dy);
    if (distance === 0) {
        return [start, end];
    }

    const curve = Math.min(distance * 0.22, 16);
    let normalLng = -dy / distance;
    let normalLat = dx / distance;
    if (normalLat < 0) {
        normalLng *= -1;
        normalLat *= -1;
    }
    const controlLng = startLng + dx / 2 + normalLng * curve;
    const controlLat = startLat + dy / 2 + normalLat * curve;
    return Array.from({length: ARC_POINT_COUNT + 1}, (_, index) => {
        const t = index / ARC_POINT_COUNT;
        const inverse = 1 - t;
        return [
            inverse * inverse * startLng + 2 * inverse * t * controlLng + t * t * endLng,
            inverse * inverse * startLat + 2 * inverse * t * controlLat + t * t * endLat,
        ];
    });
}

export function selectedPath(qth, feature) {
    const start = qthCoordinates(qth);
    const end = feature?.geometry?.coordinates;
    return start === null || !isCoordinatePair(end) ? EMPTY_FEATURE_COLLECTION : {
        type: 'FeatureCollection',
        features: [{
            type: 'Feature',
            geometry: {type: 'LineString', coordinates: generateArcCoordinates(start, end)},
            properties: {},
        }],
    };
}

export function selectionBounds(start, end) {
    const startLng = Number(start[0]);
    const startLat = Number(start[1]);
    const endLng = unwrapLongitude(startLng, end[0]);
    const endLat = Number(end[1]);
    return [
        [Math.min(startLng, endLng), Math.min(startLat, endLat)],
        [Math.max(startLng, endLng), Math.max(startLat, endLat)],
    ];
}

export function popupCoordinates(feature, lngLat = null) {
    const coordinates = feature.geometry.coordinates.slice();
    if (lngLat !== null) {
        coordinates[0] = unwrapLongitude(lngLat.lng, coordinates[0]);
    }
    return coordinates;
}
