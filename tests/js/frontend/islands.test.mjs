import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {h} from 'vue';
import {createIsland} from '../../../resources/js/support/islands.mjs';

const source = await readFile(new URL('../../../resources/js/support/islands.mjs', import.meta.url), 'utf8');
const optionsFactory = new Function('defineAsyncComponent', 'h',
    source.replace(/import[^;]+;/, '').replace('export function', 'function') + '\nreturn createIsland;',
)(options => options, h);

test('islands defer fetching until Vue requests the component', async () => {
    let loads = 0;
    const component = {render: () => h('p', 'Ready')};
    const island = createIsland(async () => {
        loads++;
        return {__esModule: true, default: component};
    }, 'Radio map');
    assert.equal(loads, 0);
    assert.equal(await island.__asyncLoader(), component);
    assert.equal(loads, 1);
});

test('failed island requests reject rather than silently disappearing', async t => {
    const errors = [];
    t.mock.method(console, 'error', (...args) => errors.push(args));
    const failure = new Error('Network unavailable');
    const island = createIsland(() => Promise.reject(failure), 'Photo gallery');
    await assert.rejects(island.__asyncLoader(), failure);
    assert.deepEqual(errors, [['Failed to load photo gallery', failure]]);
});

test('loading and failure views are accessible and offer a working reload', t => {
    const options = optionsFactory(() => Promise.resolve({}), 'Radio map');
    assert.equal(options.delay, 200);
    assert.equal(options.timeout, 30000);
    const loading = options.loadingComponent.render();
    assert.equal(loading.props.role, 'status');
    assert.match(loading.children, /radio map/);
    const error = options.errorComponent.render();
    assert.equal(error.props.role, 'alert');
    assert.match(error.children[0].children, /Radio map could not be loaded/);
    const previous = globalThis.window;
    t.after(() => { globalThis.window = previous; });
    let reloads = 0;
    globalThis.window = {location: {reload: () => reloads++}};
    error.children[1].props.onClick();
    assert.equal(reloads, 1);
});

test('Blade templates retain the compiler and only heavy islands are dynamic', async () => {
    const entry = await readFile(new URL('../../../resources/js/vue.js', import.meta.url), 'utf8');
    assert.match(entry, /vue\/dist\/vue\.esm-bundler/);
    for (const name of ['PhotoGalleryComponent', 'QSOMapComponent', 'Showcase']) {
        assert.match(entry, new RegExp(`\\(\\) => import\\('\\./components/${name}\\.vue'\\)`));
    }
    assert.doesNotMatch(entry, /vuetify|@mdi/);
});
