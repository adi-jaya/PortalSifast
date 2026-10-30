/* global globalThis, global */
import assert from 'node:assert';
import { test } from 'node:test';
import { createMockChrome } from './helpers/mock-chrome.js';

test('End-to-End Extension Flow Simulation', async (t) => {
    const mockChrome = createMockChrome();
    global.chrome = mockChrome;

    // 1. Import modules (content-autofill binds to globalThis.__sifastAutofill for MV3 classic script compatibility)
    const bg = await import('../background.js');
    await import('../content-autofill.js');
    const autofill = globalThis.__sifastAutofill;

    assert.ok(
        autofill,
        'Autofill engine must be available on globalThis.__sifastAutofill',
    );

    await t.test(
        'full lifecycle: SIMRS dispatch -> background queue -> target autofill -> memory wipe',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            // STEP 1: SIMRS triggers launch
            const simrsPayload = {
                portal: {
                    id: 5,
                    name: 'SITB Kemenkes',
                    url: 'https://jatim.sitb.id/sitb2024/app',
                    form_config: {
                        username_field: {
                            selectors: ["input[name='username']", '#user'],
                        },
                        password_field: {
                            selectors: ["input[name='password']", '#pass'],
                        },
                    },
                },
                credentials: {
                    type: 'personal',
                    username: 'tb_petugas_siti_fatimah',
                    password: 'SITB_SecretPass#2026',
                    extra_fields: {},
                },
                dispatched_at: new Date().toISOString(),
            };

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: simrsPayload,
            });

            assert.strictEqual(launchRes.success, true);
            const targetTabId = launchRes.tabId;
            assert.ok(targetTabId, 'Target tab must be assigned a numeric id');

            // Verify background holds credential temporarily
            assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);
            assert.strictEqual(bg.getQueueSize(), 1);

            // STEP 2: Target portal finishes loading and requests credentials
            const fetchRes = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                {
                    tab: {
                        id: targetTabId,
                        url: 'https://jatim.sitb.id/sitb2024/app',
                    },
                },
            );

            assert.strictEqual(fetchRes.success, true);
            assert.strictEqual(fetchRes.payload.portal.name, 'SITB Kemenkes');
            assert.strictEqual(
                fetchRes.payload.credentials.username,
                'tb_petugas_siti_fatimah',
            );

            // STEP 3: Auto-flush guarantee check in Background Worker
            assert.strictEqual(
                bg.hasPendingCredentials(targetTabId),
                false,
                'Background MUST flush credentials on consumption',
            );
            assert.strictEqual(bg.getQueueSize(), 0);

            // STEP 4: Target tab fills DOM using mock elements
            class MockElement {
                constructor(attrs = {}) {
                    this.attributes = { ...attrs };
                    this._val = '';
                    this.events = [];
                    this.focused = false;
                }
                get id() {
                    return this.attributes.id || '';
                }
                get name() {
                    return this.attributes.name || '';
                }
                get type() {
                    return this.attributes.type || 'text';
                }
                get value() {
                    return this._val;
                }
                set value(v) {
                    this._val = v;
                }
                dispatchEvent(e) {
                    this.events.push(e.type);
                    return true;
                }
                focus() {
                    this.focused = true;
                }
            }

            const userInput = new MockElement({
                name: 'username',
                type: 'text',
            });
            const passInput = new MockElement({
                name: 'password',
                type: 'password',
            });

            const mockDom = {
                querySelector(sel) {
                    if (sel === "input[name='username']") return userInput;
                    if (sel === "input[name='password']") return passInput;
                    return null;
                },
                querySelectorAll() {
                    return [userInput, passInput];
                },
            };

            const autofillResult = autofill.executeAutofill(
                fetchRes.payload,
                mockDom,
                { showToast: false },
            );

            assert.strictEqual(autofillResult.success, true);
            assert.strictEqual(userInput.value, 'tb_petugas_siti_fatimah');
            assert.strictEqual(passInput.value, 'SITB_SecretPass#2026');
            assert.ok(userInput.events.includes('input'));
            assert.ok(passInput.events.includes('change'));

            // STEP 5: Content script memory wipe check
            assert.strictEqual(
                fetchRes.payload.credentials,
                null,
                'Credentials reference in content payload must be erased',
            );
        },
    );

    await t.test(
        'integrated lifecycle with CAPTCHA detection and focus transfer',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const simrsPayload = {
                portal: {
                    id: 1,
                    name: 'SIRS Online',
                    url: 'https://sirs.kemkes.go.id/fo/login',
                    form_config: {
                        username_field: {
                            selectors: ["input[name='user']"],
                        },
                        password_field: {
                            selectors: ["input[name='pass']"],
                        },
                    },
                },
                credentials: {
                    type: 'personal',
                    username: 'sirs_admin',
                    password: 'SecurePass#2026',
                    extra_fields: {},
                },
            };

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: simrsPayload,
            });

            const targetTabId = launchRes.tabId;

            const fetchRes = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                {
                    tab: {
                        id: targetTabId,
                        url: 'https://sirs.kemkes.go.id/fo/login',
                    },
                },
            );

            class MockElement {
                constructor(attrs = {}) {
                    this.attributes = { ...attrs };
                    this._val = '';
                    this.events = [];
                    this.focused = false;
                }
                get id() {
                    return this.attributes.id || '';
                }
                get name() {
                    return this.attributes.name || '';
                }
                get type() {
                    return this.attributes.type || 'text';
                }
                get placeholder() {
                    return this.attributes.placeholder || '';
                }
                get value() {
                    return this._val;
                }
                set value(v) {
                    this._val = v;
                }
                dispatchEvent(e) {
                    this.events.push(e.type);
                    return true;
                }
                focus() {
                    this.focused = true;
                }
            }

            const userInput = new MockElement({ name: 'user', type: 'text' });
            const passInput = new MockElement({
                name: 'pass',
                type: 'password',
            });
            const captchaInput = new MockElement({
                name: 'captcha',
                type: 'text',
                placeholder: 'Masukkan Kode Keamanan',
            });

            const mockDom = {
                querySelector(sel) {
                    if (sel === "input[name='user']") return userInput;
                    if (sel === "input[name='pass']") return passInput;
                    if (sel === "input[name='captcha']") return captchaInput;
                    return null;
                },
                querySelectorAll() {
                    return [userInput, passInput, captchaInput];
                },
            };

            const autofillResult = autofill.executeAutofill(
                fetchRes.payload,
                mockDom,
                { showToast: false },
            );

            assert.strictEqual(autofillResult.success, true);
            assert.strictEqual(userInput.value, 'sirs_admin');
            assert.strictEqual(passInput.value, 'SecurePass#2026');
            assert.strictEqual(
                captchaInput.focused,
                true,
                'CAPTCHA element must receive user focus automatically',
            );
            assert.strictEqual(autofillResult.filled.captchaFocused, true);
            assert.strictEqual(fetchRes.payload.credentials, null);
        },
    );

    await t.test(
        'prevents credential leakage on second request to same tab (zero-persistence auto-flush)',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const simrsPayload = {
                portal: {
                    id: 3,
                    name: 'MPDN',
                    url: 'https://mpdn.kemkes.go.id',
                },
                credentials: {
                    type: 'shared',
                    username: 'mpdn_user',
                    password: 'Password#123',
                },
            };

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: simrsPayload,
            });

            const targetTabId = launchRes.tabId;

            // First fetch succeeds and flushes
            const firstFetch = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                { tab: { id: targetTabId } },
            );
            assert.strictEqual(firstFetch.success, true);

            // Second fetch immediately fails with NO_CREDENTIALS_OR_EXPIRED
            const secondFetch = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                { tab: { id: targetTabId } },
            );
            assert.strictEqual(secondFetch.success, false);
            assert.strictEqual(secondFetch.reason, 'NO_CREDENTIALS_OR_EXPIRED');
        },
    );
});
