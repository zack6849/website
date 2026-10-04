import {defineAsyncComponent, h} from 'vue';

export function createIsland(loader, label) {
    return defineAsyncComponent({
        loader,
        delay: 200,
        timeout: 30000,
        loadingComponent: {
            render: () => h('p', {role: 'status', class: 'p-4 text-gray-500'}, `Loading ${label.toLowerCase()}…`),
        },
        errorComponent: {
            render: () => h('div', {role: 'alert', class: 'p-4 text-red-700'}, [
                h('p', `${label} could not be loaded. Please reload to try again.`),
                h('button', {
                    type: 'button',
                    class: 'mt-2 underline',
                    onClick: () => window.location.reload(),
                }, 'Reload page'),
            ]),
        },
        onError(error, retry, fail) {
            console.error(`Failed to load ${label.toLowerCase()}`, error);
            fail();
        },
    });
}
