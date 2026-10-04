export function createRequests({get, onError = console.error}) {
    const pending = new Map();
    let disposed = false;
    return {
        async load(key, url, accept, settled = () => {}) {
            if (disposed) return;
            pending.get(key)?.abort();
            const controller = new AbortController();
            pending.set(key, controller);
            const current = () => !disposed && pending.get(key) === controller;
            try {
                const response = await get(url, {signal: controller.signal});
                if (current() && response.status === 200) accept(response.data);
            } catch (error) {
                if (current() && !controller.signal.aborted) onError(error);
            } finally {
                if (current()) {
                    pending.delete(key);
                    settled();
                }
            }
        },
        invalidate(key) {
            pending.get(key)?.abort();
            pending.delete(key);
        },
        dispose() {
            disposed = true;
            for (const controller of pending.values()) controller.abort();
            pending.clear();
        },
    };
}
