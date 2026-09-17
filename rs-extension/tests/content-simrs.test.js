import { test } from 'node:test';
import assert from 'node:assert';
import { createMockChrome } from './helpers/mock-chrome.js';

test('SIMRS Content Script Bridge (content-simrs.js)', async (t) => {
    // Mock Window, Document, CustomEvent, and EventTarget
    class MockCustomEvent {
        constructor(type, options = {}) {
            this.type = type;
            this.detail = options.detail || null;
        }
    }

    const windowListeners = new Map();
    const mockWindow = {
        addEventListener(type, fn) {
            if (!windowListeners.has(type)) windowListeners.set(type, []);
            windowListeners.get(type).push(fn);
        },
        dispatchEvent(event) {
            const listeners = windowListeners.get(event.type) || [];
            for (const fn of listeners) fn(event);
            return true;
        },
    };

    const mockDocument = {
        documentElement: {
            dataset: {},
        },
        readyState: 'complete',
        addEventListener: () => {},
    };

    const mockChrome = createMockChrome();

    global.window = mockWindow;
    global.document = mockDocument;
    global.CustomEvent = MockCustomEvent;
    global.chrome = mockChrome;

    await import('../content-simrs.js');
    const simrsBridge = globalThis.__sifastSimrsBridge;

    assert.ok(
        simrsBridge,
        'globalThis.__sifastSimrsBridge must be defined for tests',
    );

    await t.test(
        'injects dataset attributes into document.documentElement',
        () => {
            mockDocument.documentElement.dataset = {};
            simrsBridge.injectDomMarkers();

            assert.strictEqual(
                mockDocument.documentElement.dataset.sifastExtensionInstalled,
                'true',
            );
            assert.strictEqual(
                mockDocument.documentElement.dataset.sifastExtensionVersion,
                '1.0.0',
            );
        },
    );

    await t.test(
        'dispatches SIFAST_EXTENSION_READY event on initialization',
        () => {
            let capturedEvent = null;
            windowListeners.set('SIFAST_EXTENSION_READY', [
                (e) => {
                    capturedEvent = e;
                },
            ]);

            simrsBridge.notifyExtensionReady();

            assert.ok(
                capturedEvent,
                'SIFAST_EXTENSION_READY must be dispatched',
            );
            assert.strictEqual(capturedEvent.detail.installed, true);
            assert.strictEqual(capturedEvent.detail.version, '1.0.0');
        },
    );

    await t.test(
        'responds to SIFAST_PING_EXTENSION with SIFAST_PONG_EXTENSION',
        () => {
            let pongEvent = null;
            windowListeners.set('SIFAST_PONG_EXTENSION', [
                (e) => {
                    pongEvent = e;
                },
            ]);

            simrsBridge.initSimrsBridge();

            // Trigger ping from SIMRS React page
            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PING_EXTENSION'),
            );

            assert.ok(
                pongEvent,
                'SIFAST_PONG_EXTENSION must be dispatched on ping',
            );
            assert.strictEqual(pongEvent.detail.installed, true);
            assert.strictEqual(pongEvent.detail.version, '1.0.0');
        },
    );

    await t.test(
        'relays SIFAST_PORTAL_LAUNCH event to background service worker',
        async () => {
            let messageSent = null;
            mockChrome.runtime.lastError = null;
            mockChrome.runtime.sendMessage = (msg, cb) => {
                messageSent = msg;
                if (cb) cb({ success: true, tabId: 1005 });
            };

            let ackEvent = null;
            windowListeners.set('SIFAST_PORTAL_LAUNCH_ACK', [
                (e) => {
                    ackEvent = e;
                },
            ]);

            simrsBridge.initSimrsBridge();

            const portalDetail = {
                portal: {
                    id: 1,
                    name: 'SIRS Online',
                    url: 'https://akun-yankes.kemkes.go.id/',
                },
                credentials: { username: 'test', password: '123' },
            };

            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: portalDetail,
                }),
            );

            assert.ok(messageSent, 'chrome.runtime.sendMessage must be called');
            assert.strictEqual(messageSent.type, 'SIFAST_PORTAL_LAUNCH');
            assert.deepStrictEqual(messageSent.payload, portalDetail);

            assert.ok(
                ackEvent,
                'SIFAST_PORTAL_LAUNCH_ACK event must be dispatched to window',
            );
            assert.strictEqual(ackEvent.detail.success, true);
            assert.strictEqual(ackEvent.detail.tabId, 1005);
        },
    );

    await t.test(
        'handles empty SIFAST_PORTAL_LAUNCH gracefully without relaying',
        () => {
            let messageSent = null;
            mockChrome.runtime.sendMessage = (msg) => {
                messageSent = msg;
            };

            simrsBridge.initSimrsBridge();
            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PORTAL_LAUNCH', { detail: null }),
            );

            assert.strictEqual(
                messageSent,
                null,
                'Must not send message when detail is missing',
            );
        },
    );

    await t.test(
        'dispatches fallback ACK when background service worker returns empty response',
        () => {
            mockChrome.runtime.lastError = null;
            mockChrome.runtime.sendMessage = (msg, cb) => {
                if (cb) cb(null);
            };

            let ackEvent = null;
            windowListeners.set('SIFAST_PORTAL_LAUNCH_ACK', [
                (e) => {
                    ackEvent = e;
                },
            ]);

            simrsBridge.initSimrsBridge();
            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: { portal: {} },
                }),
            );

            assert.ok(
                ackEvent,
                'SIFAST_PORTAL_LAUNCH_ACK must be dispatched even on null response',
            );
            assert.strictEqual(ackEvent.detail.success, false);
            assert.strictEqual(
                ackEvent.detail.error,
                'No response from extension background',
            );
        },
    );

    await t.test(
        'dispatches error ACK when chrome.runtime.lastError is present',
        () => {
            mockChrome.runtime.lastError = {
                message:
                    'Could not establish connection. Receiving end does not exist.',
            };
            mockChrome.runtime.sendMessage = (msg, cb) => {
                if (cb) cb(null);
            };

            let ackEvent = null;
            windowListeners.set('SIFAST_PORTAL_LAUNCH_ACK', [
                (e) => {
                    ackEvent = e;
                },
            ]);

            simrsBridge.initSimrsBridge();
            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: { portal: {} },
                }),
            );

            assert.ok(ackEvent, 'ACK must be dispatched on lastError');
            assert.strictEqual(ackEvent.detail.success, false);
            assert.strictEqual(
                ackEvent.detail.error,
                'Could not establish connection. Receiving end does not exist.',
            );
            mockChrome.runtime.lastError = null;
        },
    );

    await t.test(
        'dispatches error ACK when chrome.runtime.sendMessage is unavailable or throws',
        () => {
            const originalSendMessage = mockChrome.runtime.sendMessage;
            mockChrome.runtime.sendMessage = () => {
                throw new Error('Extension context invalidated.');
            };

            let ackEvent = null;
            windowListeners.set('SIFAST_PORTAL_LAUNCH_ACK', [
                (e) => {
                    ackEvent = e;
                },
            ]);

            simrsBridge.initSimrsBridge();
            mockWindow.dispatchEvent(
                new MockCustomEvent('SIFAST_PORTAL_LAUNCH', {
                    detail: { portal: {} },
                }),
            );

            assert.ok(ackEvent, 'ACK must be dispatched on error throw');
            assert.strictEqual(ackEvent.detail.success, false);
            assert.strictEqual(
                ackEvent.detail.error,
                'Extension context invalidated.',
            );

            mockChrome.runtime.sendMessage = originalSendMessage;
        },
    );
});
