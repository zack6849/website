import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile, readdir} from 'node:fs/promises';
import {library, icon} from '@fortawesome/fontawesome-svg-core';
import * as solid from '@fortawesome/free-solid-svg-icons';
import * as brands from '@fortawesome/free-brands-svg-icons';

const root = new URL('../../../', import.meta.url);
const source = await readFile(new URL('resources/js/icons.js', root), 'utf8');
let watches = 0;
// Evaluate the real registry, replacing only DOM observation with a spy.
new Function('library', 'dom', ...Object.keys({...solid, ...brands}),
    source.replace(/import[\s\S]*?from\s+['"][^'"]+['"];?/g, ''),
)(library, {watch: () => watches++}, ...Object.values({...solid, ...brands}));

async function sources(directory) {
    const result = [];
    for (const entry of await readdir(directory, {withFileTypes: true})) {
        const path = new URL(entry.name + (entry.isDirectory() ? '/' : ''), directory);
        if (entry.isDirectory()) result.push(...await sources(path));
        else if (/\.(php|vue|js|mjs)$/.test(entry.name)) result.push([path, await readFile(path, 'utf8')]);
    }
    return result;
}

test('every Blade, Vue, and config icon is registered, including legacy aliases', async () => {
    const files = [
        ...await sources(new URL('resources/views/', root)),
        ...await sources(new URL('resources/js/components/', root)),
        ...await sources(new URL('config/', root)),
    ];
    const modifiers = /^(solid|brands|regular|stack(?:-\dx)?|flip-horizontal|inverse)$/;
    let checked = 0;
    for (const [path, text] of files) {
        for (const [, name] of text.matchAll(/\bfa-([a-z][a-z0-9-]*)\b/g)) {
            if (modifiers.test(name)) continue;
            const prefix = library.definitions.fab?.[name] ? 'fab' : 'fas';
            assert.ok(icon({prefix, iconName: name}), `${path.pathname}: fa-${name}`);
            checked++;
        }
    }
    assert.ok(checked > 30, 'coverage includes config icons and notifications');
    assert.equal(watches, 1, 'observe asynchronous Vue and Livewire DOM updates');
});

test('SVG output preserves stacked and flipped project icons', () => {
    const circle = icon({prefix: 'fas', iconName: 'circle'}, {classes: ['fa-stack-2x']});
    const foreground = icon({prefix: 'fas', iconName: 'person-walking-luggage'}, {
        classes: ['fa-stack-1x', 'fa-inverse'],
        transform: {flipX: true},
    });
    assert.match(circle.html.join(''), /fa-stack-2x/);
    assert.match(foreground.html.join(''), /fa-stack-1x fa-inverse/);
    assert.match(foreground.html.join(''), /scale\(-1, 1\)/);
});
