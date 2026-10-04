import test from 'node:test';
import assert from 'node:assert/strict';
import {
    buildPopupDescription, formatDistance, formatRelativeAge, formatDate,
    contactId, contactKey, contactRowId, directionArrowStyle, formatBearingTitle,
} from '../../../resources/js/support/qso-map/presentation.mjs';
import {contactLayers, globeStyle} from '../../../resources/js/support/qso-map/style.mjs';
import {feature} from './fakes.mjs';

test('all popup API fields are escaped and optional fields retain their presentation', () => {
    const unsafe = `<script>"&'</script>`;
    const properties = Object.fromEntries([
        'mode', 'mode_label', 'band', 'to_callsign', 'display_location', 'qso_date',
        'frequency', 'rst_received', 'to_grid', 'park_reference', 'park_name',
        'park_location', 'distance', 'bearing_cardinal', 'comments',
    ].map(field => [field, unsafe]));
    properties.category = 'POTA';
    const html = buildPopupDescription(properties);
    assert.ok(!html.includes('<script>'));
    assert.ok(html.includes('&lt;script&gt;&quot;&amp;&#39;&lt;/script&gt;'));
    assert.ok(html.includes('<b>POTA:</b>'));
    assert.ok(!html.includes('<b>Mode Type:</b>'));
    const minimal = buildPopupDescription({});
    for (const field of ['Location', 'POTA', 'RST Received', 'Grid', 'Distance', 'Comments', 'Mode Type']) {
        assert.ok(!minimal.includes(`<b>${field}:</b>`));
    }
    assert.ok(buildPopupDescription({mode: 'FT8', mode_label: 'Digital'}).includes('<b>Mode Type:</b> Digital'));
    assert.ok(buildPopupDescription({created_at: 'fallback', to_country: 'US'}).includes('<b>Date:</b> fallback'));
});

test('distance, relative age, bearings, dates and contact identifiers keep display semantics', () => {
    assert.equal(formatDistance(null), '-');
    assert.equal(formatDistance('', 'NE'), 'NE');
    assert.equal(formatDistance(12.4, 'N', true), '~12 mi N');
    assert.equal(formatDistance('unknown'), 'unknown mi');
    for (const [age, label] of [[null, ''], ['bad', ''], [0, 'today'], [1, '1 day ago'],
        [29, '29 days ago'], [30, '1 month ago'], [60, '2 months ago'], [365, '1 year ago'], [730, '2 years ago']]) {
        assert.equal(formatRelativeAge(age), label);
    }
    assert.equal(formatDate(''), '-');
    assert.equal(formatDate('bad date'), 'bad date');
    assert.deepEqual(directionArrowStyle(90), {transform: 'rotate(90deg)'});
    assert.deepEqual(directionArrowStyle('bad'), {});
    assert.equal(formatBearingTitle({bearing_degrees: 89.9, bearing_cardinal: 'E'}), 'Bearing E (90°)');
    assert.equal(contactId(feature(0)), '0');
    assert.equal(contactRowId(feature(1)), 'qso-row-1');
    assert.equal(contactKey({properties: {qso_date: 'date'}}, 2), 'date-2');
});

test('map layers retain order, recency styling, icons, and transparent globe atmosphere', () => {
    const layers = contactLayers();
    assert.deepEqual(layers.map(layer => layer.id), [
        'selected-qso-path-casing', 'selected-qso-path', 'qso-recency',
        'qso-highlight', 'qso-badge', 'qsos', 'qth',
    ]);
    assert.deepEqual(layers[2].paint['circle-radius'], [
        'interpolate', ['linear'], ['to-number', ['get', 'recency_score'], 0.35], 0, 4, 0.5, 9, 1, 18,
    ]);
    assert.equal(layers[5].layout['icon-allow-overlap'], true);
    assert.equal(layers[6].layout['icon-image'], 'qth-antenna');
    const base = {version: 8, layers: []};
    const style = globeStyle(null, base);
    assert.equal(style.layers, base.layers);
    assert.deepEqual(style.projection, {type: 'globe'});
    assert.equal(style.sky['sky-color'], 'transparent');
    assert.equal(style.sky['atmosphere-blend'], 0.8);
    assert.equal(base.projection, undefined);
});
