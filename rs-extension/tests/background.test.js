/* global globalThis */
import assert from 'node:assert';
import { test } from 'node:test';
import { createMockChrome } from './helpers/mock-chrome.js';

test('Background Service Worker Credential Queue', async (t) => {
    // Global mock environment setup
    const mockChrome = createMockChrome();
    globalThis.chrome = mockChrome;

    // Dynamically import background script
    const bg = await import('../background.js');

    await t.test(
        'stores credentials upon SIFAST_PORTAL_LAUNCH and opens new tab',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const launchPayload = {
                portal: {
                    id: 1,
                    name: 'SIRS Online',
                    url: 'https://akun-yankes.kemkes.go.id/',
                },
                credentials: {
                    type: 'shared',
                    username: 'sifast_user',
                    password: 'SecretPassword123!',
                },
                dispatched_at: new Date().toISOString(),
            };

            const response = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: launchPayload,
            });

            assert.strictEqual(response.success, true);
            assert.strictEqual(typeof response.tabId, 'number');
            const targetTabId = response.tabId;

            // Verify queue stores it in memory
            assert.strictEqual(bg.getQueueSize(), 1);
            assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);
        },
    );

    await t.test(
        'auto-flushes credentials immediately when fetched by target tab (zero-persistence)',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const launchPayload = {
                portal: {
                    id: 2,
                    name: 'SITB',
                    url: 'https://jatim.sitb.id/sitb2024/app',
                },
                credentials: {
                    type: 'personal',
                    username: 'petugas_sitb',
                    password: 'P@sswordSITB!',
                },
            };

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: launchPayload,
            });
            const targetTabId = launchRes.tabId;

            // Simulate target content script requesting credentials
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
            assert.strictEqual(
                fetchRes.payload.credentials.username,
                'petugas_sitb',
            );
            assert.strictEqual(
                fetchRes.payload.credentials.password,
                'P@sswordSITB!',
            );

            // AUTO-FLUSH VERIFICATION: Queue must immediately be cleared for this tab
            assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
            assert.strictEqual(bg.getQueueSize(), 0);

            // Second request must fail with NO_CREDENTIALS_OR_EXPIRED
            const secondFetchRes = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                { tab: { id: targetTabId } },
            );
            assert.strictEqual(secondFetchRes.success, false);
            assert.strictEqual(
                secondFetchRes.reason,
                'NO_CREDENTIALS_OR_EXPIRED',
            );
        },
    );

    await t.test(
        'expires credentials automatically after TTL (30s timeout)',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const targetTabId = 999;
            bg.storeCredentials(
                targetTabId,
                {
                    portal: {
                        id: 3,
                        name: 'SIRIKA',
                        url: 'https://siga-sirika.bkkbn.go.id/login',
                    },
                    credentials: { username: 'bkkbn_user', password: 'pass' },
                },
                50 /* 50ms TTL for test */,
            );

            assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);

            // Wait for TTL expiry
            await new Promise((resolve) => setTimeout(resolve, 80));

            assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
            assert.strictEqual(bg.getQueueSize(), 0);
        },
    );

    await t.test(
        'cleans up pending credentials if tab is closed before autofill (chrome.tabs.onRemoved)',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: {
                    portal: {
                        id: 4,
                        name: 'SIGA',
                        url: 'https://newsiga-siga.kemendukbangga.go.id/#/login',
                    },
                    credentials: { username: 'user_siga', password: 'pwd' },
                },
            });
            const targetTabId = launchRes.tabId;
            assert.strictEqual(bg.hasPendingCredentials(targetTabId), true);

            // Simulate tab closed by user
            mockChrome.tabs.onRemoved._trigger(targetTabId);

            // Verify cleanup
            assert.strictEqual(bg.hasPendingCredentials(targetTabId), false);
            assert.strictEqual(bg.getQueueSize(), 0);
        },
    );

    await t.test(
        'rejects portal launch if URL scheme is not http:// or https://',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const invalidUrls = [
                'javascript:alert(1)',
                'chrome://settings',
                'file:///etc/passwd',
                'data:text/html,<script>alert(1)</script>',
                'ftp://ftp.example.com',
            ];

            for (const url of invalidUrls) {
                const response = await mockChrome.runtime.onMessage._trigger({
                    type: 'SIFAST_PORTAL_LAUNCH',
                    payload: {
                        portal: { id: 99, name: 'Invalid Scheme Portal', url },
                        credentials: { username: 'user', password: 'pwd' },
                    },
                });

                assert.strictEqual(response.success, false);
                assert.strictEqual(
                    response.error,
                    'Invalid portal URL scheme. Must be http:// or https://',
                );
                assert.strictEqual(bg.getQueueSize(), 0);
            }
        },
    );

    await t.test(
        'rejects credential retrieval if sender tab origin does not match queued portal origin',
        async () => {
            mockChrome._reset();
            bg.initBackground();

            const launchPayload = {
                portal: {
                    id: 1,
                    name: 'Target Portal',
                    url: 'https://jatim.sitb.id/sitb2024/app',
                },
                credentials: {
                    username: 'admin',
                    password: 'secret',
                },
            };

            const launchRes = await mockChrome.runtime.onMessage._trigger({
                type: 'SIFAST_PORTAL_LAUNCH',
                payload: launchPayload,
            });
            const targetTabId = launchRes.tabId;

            // Simulate malicious content script from a different origin attempting to fetch credentials
            const fetchRes = await mockChrome.runtime.onMessage._trigger(
                { type: 'SIFAST_GET_CREDENTIALS' },
                {
                    tab: {
                        id: targetTabId,
                        url: 'https://evil-phishing-portal.com/login',
                    },
                },
            );

            assert.strictEqual(fetchRes.success, false);
            assert.strictEqual(fetchRes.reason, 'ORIGIN_MISMATCH');
            // Pending credentials should NOT be flushed to the malicious sender
            assert.strictEqual(bg.getQueueSize(), 1);
        },
    );
});
