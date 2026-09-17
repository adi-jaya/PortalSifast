/* global chrome */
/**
 * SIFAST Portal Autofill Extension - Background Service Worker (Manifest V3)
 *
 * Mengelola antrean kredensial sementara dalam memori RAM (Zero-Persistence).
 * - Tidak pernah menulis data kredensial ke storage lokal / disk.
 * - Indexed by target tabId.
 * - TTL 30 detik auto-expire.
 * - Auto-flush seketika setelah diambil oleh target tab.
 */

const pendingCredentials = new Map();
const TTL_MS = 30000; // 30 seconds TTL

/**
 * Menyimpan kredensial dalam antrean memori RAM.
 */
export function storeCredentials(tabId, payload, customTtlMs = TTL_MS) {
    if (pendingCredentials.has(tabId)) {
        const existing = pendingCredentials.get(tabId);
        if (existing.timeoutId) clearTimeout(existing.timeoutId);
    }

    const timeoutId = setTimeout(() => {
        pendingCredentials.delete(tabId);
        console.log(
            `[SIFAST Background] Credentials expired for tabId: ${tabId}`,
        );
    }, customTtlMs);

    if (typeof timeoutId === 'object' && typeof timeoutId?.unref === 'function') {
        timeoutId.unref();
    }

    pendingCredentials.set(tabId, {
        portal: payload.portal,
        credentials: payload.credentials,
        dispatchedAt: payload.dispatched_at || new Date().toISOString(),
        createdAt: Date.now(),
        timeoutId,
    });

    console.log(
        `[SIFAST Background] Credentials queued for tabId: ${tabId} (TTL: ${customTtlMs}ms)`,
    );
}

/**
 * Mengambil kredensial sekaligus menghapusnya dari RAM (Auto-Flush / Zero Persistence).
 */
export function getAndFlushCredentials(tabId) {
    if (!pendingCredentials.has(tabId)) {
        return null;
    }

    const entry = pendingCredentials.get(tabId);
    if (entry.timeoutId) {
        clearTimeout(entry.timeoutId);
    }

    // Hapus seketika dari RAM
    pendingCredentials.delete(tabId);
    console.log(
        `[SIFAST Background] Credentials consumed and flushed for tabId: ${tabId}`,
    );

    return {
        portal: entry.portal,
        credentials: entry.credentials,
        dispatchedAt: entry.dispatchedAt,
    };
}

/**
 * Hapus kredensial tab tertentu.
 */
export function removeCredentials(tabId) {
    if (pendingCredentials.has(tabId)) {
        const entry = pendingCredentials.get(tabId);
        if (entry.timeoutId) clearTimeout(entry.timeoutId);
        pendingCredentials.delete(tabId);
    }
}

/**
 * Cek status antrean tab tertentu.
 */
export function hasPendingCredentials(tabId) {
    return pendingCredentials.has(tabId);
}

/**
 * Mendapatkan jumlah antrean kredensial saat ini.
 */
export function getQueueSize() {
    return pendingCredentials.size;
}

/**
 * Inisialisasi event listener Chrome runtime & tabs.
 */
export function initBackground() {
    if (typeof chrome === 'undefined' || !chrome.runtime) {
        return;
    }

    // Message router
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        if (!message || !message.type) return false;

        switch (message.type) {
            case 'SIFAST_PORTAL_LAUNCH': {
                const payload = message.payload;
                if (!payload || !payload.portal || !payload.portal.url) {
                    sendResponse({
                        success: false,
                        error: 'Invalid portal launch payload',
                    });
                    return false;
                }

                const portalUrl = String(payload.portal.url);
                if (
                    !portalUrl.startsWith('http://') &&
                    !portalUrl.startsWith('https://')
                ) {
                    sendResponse({
                        success: false,
                        error: 'Invalid portal URL scheme. Must be http:// or https://',
                    });
                    return false;
                }

                chrome.tabs.create(
                    { url: payload.portal.url, active: true },
                    (tab) => {
                        if (!tab || !tab.id) {
                            sendResponse({
                                success: false,
                                error: 'Failed to create target tab',
                            });
                            return;
                        }

                        storeCredentials(tab.id, payload);
                        sendResponse({ success: true, tabId: tab.id });
                    },
                );

                return true; // Asynchronous sendResponse
            }

            case 'SIFAST_GET_CREDENTIALS': {
                const tabId = sender.tab ? sender.tab.id : message.tabId;
                if (!tabId) {
                    sendResponse({
                        success: false,
                        reason: 'NO_TAB_IDENTIFIER',
                    });
                    return false;
                }

                // Origin validation: verify sender tab origin matches target portal origin
                const entry = pendingCredentials.get(tabId);
                const portalUrl = entry?.portal?.url;
                if (entry && sender.tab?.url && portalUrl) {
                    try {
                        const senderOrigin = new URL(sender.tab.url).origin;
                        const targetOrigin = new URL(portalUrl).origin;
                        if (senderOrigin !== targetOrigin) {
                            console.warn(
                                `[SIFAST Background] Origin mismatch for tabId ${tabId}: ${senderOrigin} !== ${targetOrigin}`,
                            );
                            sendResponse({
                                success: false,
                                reason: 'ORIGIN_MISMATCH',
                            });
                            return false;
                        }
                    } catch {
                        // Ignore parse errors for non-standard test URLs
                    }
                }

                const data = getAndFlushCredentials(tabId);
                if (data) {
                    sendResponse({ success: true, payload: data });
                } else {
                    sendResponse({
                        success: false,
                        reason: 'NO_CREDENTIALS_OR_EXPIRED',
                    });
                }
                return false;
            }

            case 'SIFAST_GET_EXTENSION_STATUS': {
                sendResponse({
                    success: true,
                    version: '1.0.0',
                    queueSize: pendingCredentials.size,
                });
                return false;
            }

            default:
                return false;
        }
    });

    // Tab closed cleanup
    if (chrome.tabs && chrome.tabs.onRemoved) {
        chrome.tabs.onRemoved.addListener((tabId) => {
            removeCredentials(tabId);
        });
    }
}

// Jalankan inisialisasi di environment browser
initBackground();
