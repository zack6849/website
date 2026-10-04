export const EMPTY_HIGHLIGHT_FILTER = ['==', ['to-string', ['get', 'id']], '__none__'];
export const QSO_LAYERS = ['qsos', 'qso-badge', 'qso-highlight', 'qso-recency'];

export function globeStyle(_previousStyle, nextStyle) {
    return {
        ...nextStyle,
        projection: {type: 'globe'},
        sky: {
            'sky-color': 'transparent',
            'horizon-color': 'transparent',
            'fog-color': 'transparent',
            'fog-ground-blend': 0,
            'horizon-fog-blend': 0,
            'atmosphere-blend': 0.8,
        },
    };
}

function recency(...stops) {
    return ['interpolate', ['linear'], ['to-number', ['get', 'recency_score'], 0.35], ...stops];
}

function bandColor() {
    return ['to-color', ['get', 'band_color'], '#64748b'];
}

export function contactLayers() {
    return [
        {
            id: 'selected-qso-path-casing', type: 'line', source: 'selected-qso-path',
            paint: {'line-color': '#ffffff', 'line-width': 8, 'line-opacity': 0.88},
        },
        {
            id: 'selected-qso-path', type: 'line', source: 'selected-qso-path',
            paint: {
                'line-color': '#0f766e', 'line-width': 4, 'line-opacity': 0.9,
                'line-dasharray': [1.5, 1],
            },
        },
        {
            id: 'qso-recency', type: 'circle', source: 'qsos',
            paint: {
                'circle-radius': recency(0, 4, 0.5, 9, 1, 18),
                'circle-color': bandColor(),
                'circle-opacity': recency(0, 0.08, 0.5, 0.18, 1, 0.4),
                'circle-blur': recency(0, 0.9, 1, 1.35),
                'circle-stroke-color': bandColor(),
                'circle-stroke-opacity': recency(0, 0, 1, 0.55),
                'circle-stroke-width': recency(0, 0, 1, 1.5),
            },
        },
        {
            id: 'qso-highlight', type: 'circle', source: 'qsos', filter: EMPTY_HIGHLIGHT_FILTER,
            paint: {
                'circle-radius': 24, 'circle-color': '#0f766e', 'circle-opacity': 0.18,
                'circle-stroke-color': '#0f766e', 'circle-stroke-opacity': 0.65,
                'circle-stroke-width': 2,
            },
        },
        {
            id: 'qso-badge', type: 'circle', source: 'qsos',
            paint: {
                'circle-radius': recency(0, 10, 0.6, 12, 1, 15),
                'circle-color': bandColor(),
                'circle-opacity': recency(0, 0.55, 0.5, 0.8, 1, 1),
                'circle-stroke-color': '#ffffff', 'circle-stroke-width': 1.5,
                'circle-stroke-opacity': recency(0, 0.55, 0.5, 0.8, 1, 1),
            },
        },
        {
            id: 'qsos', type: 'symbol', source: 'qsos',
            layout: {
                'icon-image': ['coalesce', ['image', ['get', 'mode_icon']], ['image', 'mode-other']],
                'icon-size': recency(0, 0.3, 0.6, 0.38, 1, 0.48),
                'icon-allow-overlap': true,
            },
            paint: {'icon-opacity': recency(0, 0.35, 0.5, 0.7, 1, 1)},
        },
        {
            id: 'qth', type: 'symbol', source: 'qth',
            layout: {
                'icon-image': 'qth-antenna', 'icon-size': 0.4, 'icon-allow-overlap': true,
                'text-field': ['get', 'label'],
                'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                'text-size': 12, 'text-anchor': 'top', 'text-offset': [0, 1.45],
                'text-allow-overlap': true,
            },
            paint: {'text-color': '#0f172a', 'text-halo-color': '#ffffff', 'text-halo-width': 1.25},
        },
    ];
}
