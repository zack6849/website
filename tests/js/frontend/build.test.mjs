import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile, readdir} from 'node:fs/promises';

const root = new URL('../../../', import.meta.url);

test('production graph defers MapLibre JS, CSS, and worker to the radio island', async () => {
    const manifest = JSON.parse(await readFile(new URL('public/build/manifest.json', root), 'utf8'));
    const mapKey = 'resources/js/components/QSOMapComponent.vue';
    const map = manifest[mapKey];
    assert.ok(map.isDynamicEntry);
    assert.ok(manifest['resources/js/vue.js'].dynamicImports.includes(mapKey));
    function eagerKeys(key, seen = new Set()) {
        if (seen.has(key)) return seen;
        seen.add(key);
        for (const imported of manifest[key].imports ?? []) eagerKeys(imported, seen);
        return seen;
    }
    for (const entry of ['resources/js/app.js', 'resources/js/vue.js',
        'resources/js/components/Showcase.vue', 'resources/js/components/PhotoGalleryComponent.vue']) {
        for (const key of eagerKeys(entry)) {
            assert.notEqual(key, mapKey);
            const js = await readFile(new URL(`public/build/${manifest[key].file}`, root), 'utf8');
            assert.doesNotMatch(js, /maplibre-gl-worker|MapLibre GL JS/);
            assert.ok(!(manifest[key].css ?? []).some(css => map.css.includes(css)));
        }
    }
    const mapJs = await readFile(new URL(`public/build/${map.file}`, root), 'utf8');
    assert.match(mapJs, /maplibre-gl-worker/);
    const mapCss = await readFile(new URL(`public/build/${map.css[0]}`, root), 'utf8');
    assert.match(mapCss, /\.maplibregl-/);
    const assets = await readdir(new URL('public/build/assets/', root));
    assert.ok(!assets.some(name => /materialdesignicons|\.woff2?$|\.ttf$|\.eot$/.test(name)));
});

test('banner CSS leaves public image resolution to HomeBanner, not Vite', async () => {
    const css = await readFile(new URL('resources/css/app.css', root), 'utf8');
    assert.doesNotMatch(css, /url\(['"]?\/img\/bg\/pier_night\.jpg/);
    const vite = await readFile(new URL('vite.config.mjs', root), 'utf8');
    assert.doesNotMatch(vite, /publicDir\s*:\s*['"]public['"]/);
});
