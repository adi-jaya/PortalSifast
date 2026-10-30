export function createMockChrome() {
    const messageListeners = [];
    const tabRemovedListeners = [];
    let nextTabId = 1001;

    const mockTabs = new Map();

    return {
        runtime: {
            onMessage: {
                addListener(fn) {
                    messageListeners.push(fn);
                },
                _trigger(message, sender = {}) {
                    return new Promise((resolve) => {
                        let responded = false;
                        const sendResponse = (res) => {
                            responded = true;
                            resolve(res);
                        };
                        for (const listener of messageListeners) {
                            const result = listener(
                                message,
                                sender,
                                sendResponse,
                            );
                            if (result === true) {
                                // Async response handled via sendResponse callback
                                return;
                            }
                        }
                        if (!responded) resolve(undefined);
                    });
                },
            },
        },
        tabs: {
            create({ url, active }, callback) {
                const tab = { id: nextTabId++, url, active: active ?? true };
                mockTabs.set(tab.id, tab);
                if (callback) callback(tab);
                return Promise.resolve(tab);
            },
            onRemoved: {
                addListener(fn) {
                    tabRemovedListeners.push(fn);
                },
                _trigger(tabId) {
                    mockTabs.delete(tabId);
                    for (const listener of tabRemovedListeners) {
                        listener(tabId);
                    }
                },
            },
            _get(tabId) {
                return mockTabs.get(tabId);
            },
        },
        _reset() {
            messageListeners.length = 0;
            tabRemovedListeners.length = 0;
            mockTabs.clear();
            nextTabId = 1001;
        },
    };
}
