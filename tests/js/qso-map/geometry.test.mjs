import test from 'node:test';
import assert from 'node:assert/strict';
import {
    generateArcCoordinates, unwrapLongitude, selectionBounds, popupCoordinates,
    qthCoordinates, qthFeatureCollection, selectedPath, isCoordinatePair,
} from '../../../resources/js/support/qso-map/geometry.mjs';
import {feature} from './fakes.mjs';

test('short quadratic arcs preserve exact endpoints and proportional curvature without a minimum', () => {
    for (const distance of [0.00001, 0.01, 1, 10, 100]) {
        const arc = generateArcCoordinates([0, 0], [distance, 0]);
        assert.equal(arc.length, 49);
        assert.deepEqual(arc[0], [0, 0]);
        assert.deepEqual(arc.at(-1), [distance, 0]);
        assert.deepEqual(arc[24], [distance / 2, Math.min(distance * 0.22, 16) / 2]);
    }
    assert.deepEqual(generateArcCoordinates([2, 3], [2, 3]), [[2, 3], [2, 3]]);
    const westward = generateArcCoordinates([10, 0], [0, 0]);
    assert.ok(westward[24][1] > 0);
});

test('arcs and bounds take the short antimeridian route in both directions', () => {
    assert.equal(unwrapLongitude(179, -179), 181);
    assert.equal(unwrapLongitude(-179, 179), -181);
    assert.equal(unwrapLongitude(0, 180), 180);
    assert.equal(unwrapLongitude(0, -180), -180);
    for (const [start, end, unwrapped] of [[179, -179, 181], [-179, 179, -181]]) {
        const arc = generateArcCoordinates([start, 20], [end, 30]);
        assert.deepEqual(arc.at(-1), [unwrapped, 30]);
        assert.ok(arc.every(([lng]) => Math.abs(lng - start) < 3));
        assert.deepEqual(selectionBounds([start, 20], [end, 30]), [
            [Math.min(start, unwrapped), 20], [Math.max(start, unwrapped), 30],
        ]);
    }
});

test('popup world copies never mutate contact geometry', () => {
    const contact = feature(1, [-179, 30]);
    assert.deepEqual(popupCoordinates(contact, {lng: 179}), [181, 30]);
    assert.deepEqual(popupCoordinates(contact), [-179, 30]);
    assert.deepEqual(contact.geometry.coordinates, [-179, 30]);
});

test('QTH and selected-path collections preserve labels and reject invalid coordinates', () => {
    assert.deepEqual(qthCoordinates({lng: '12', lat: '34'}), [12, 34]);
    for (const qth of [undefined, {lat: 91, lng: 0}, {lat: 0, lng: 181}, {lat: 'bad', lng: 0}]) {
        assert.equal(qthCoordinates(qth), null);
        assert.equal(qthFeatureCollection(qth).features.length, 0);
        assert.equal(selectedPath(qth, feature()).features.length, 0);
    }
    assert.equal(qthFeatureCollection({lng: 0, lat: 0}).features[0].properties.label, 'Approx. QTH');
    assert.equal(qthFeatureCollection({lng: 0, lat: 0, label: 'Home'}).features[0].properties.label, 'Home');
    assert.equal(selectedPath({lng: 0, lat: 0}, feature()).features[0].geometry.type, 'LineString');
    for (const value of [null, [], [1], [NaN, 2], ['bad', 2]]) {
        assert.equal(isCoordinatePair(value), false);
        assert.equal(selectedPath({lng: 0, lat: 0}, feature(1, value)).features.length, 0);
    }
});
