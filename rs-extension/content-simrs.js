/**
 * SIFAST Portal Autofill Extension - SIMRS Content Bridge
 *
 * Berjalan di domain SIMRS Sifast (run_at: document_start).
 * 1. Menginjeksi dataset DOM agar React/Inertia dapat langsung mendeteksi ekstensi.
 * 2. Mengirim sinyal SIFAST_EXTENSION_READY dan merespons SIFAST_PING_EXTENSION.
 * 3. Menjembatani event SIFAST_PORTAL_LAUNCH dari halaman React ke Service Worker.
 */

const EXTENSION_VERSION = '1.0.0';

/**
 * Injeksi atribut dataset pada elemen <html>.
 */
export function injectDomMarkers() {
    if (typeof document !== 'undefined' && document.documentElement) {
        document.documentElement.dataset.sifastExtensionInstalled = 'true';
        document.documentElement.dataset.sifastExtensionVersion =
            EXTENSION_VERSION;
    }
}

/**
 * Memancarkan event CustomEvent SIFAST_EXTENSION_READY ke window.
 */
export function notifyExtensionReady() {
    if (typeof window !== 'undefined' && typeof CustomEvent !== 'undefined') {
        window.dispatchEvent(
            new CustomEvent('SIFAST_EXTENSION_READY', {
                detail: {
                    installed: true,
                    version: EXTENSION_VERSION,
                },
            }),
        );
    }
}

/**
 * Inisialisasi bridge komunikasi antara SIMRS dan ekstensi.
 */
export function initSimrsBridge() {
    injectDomMarkers();
    notifyExtensionReady();

    if (typeof window === 'undefined') return;

    // Listener untuk navigasi SPA / Ping dari React
    window.addEventListener('SIFAST_PING_EXTENSION', () => {
        injectDomMarkers();
        window.dispatchEvent(
            new CustomEvent('SIFAST_PONG_EXTENSION', {
                detail: {
                    installed: true,
                    version: EXTENSION_VERSION,
                },
            }),
        );
    });

    // Listener pemicu peluncuran portal dari tombol SIMRS
    window.addEventListener('SIFAST_PORTAL_LAUNCH', (event) => {
        if (!event || !event.detail) {
            console.warn(
                '[SIFAST Bridge] Ignored empty SIFAST_PORTAL_LAUNCH event',
            );
            return;
        }

        const payload = event.detail;

        if (
            typeof chrome !== 'undefined' &&
            chrome.runtime &&
            chrome.runtime.sendMessage
        ) {
            chrome.runtime.sendMessage(
                {
                    type: 'SIFAST_PORTAL_LAUNCH',
                    payload: payload,
                },
                (response) => {
                    window.dispatchEvent(
                        new CustomEvent('SIFAST_PORTAL_LAUNCH_ACK', {
                            detail: response || {
                                success: false,
                                error: 'No response from extension background',
                            },
                        }),
                    );
                },
            );
        } else {
            console.error(
                '[SIFAST Bridge] chrome.runtime.sendMessage is not available',
            );
        }
    });

    console.log(
        `[SIFAST Bridge] Extension v${EXTENSION_VERSION} attached to SIMRS portal.`,
    );
}

// Jalankan otomatis di browser
if (typeof window !== 'undefined') {
    initSimrsBridge();
    if (
        typeof document !== 'undefined' &&
        document.readyState === 'loading' &&
        typeof document.addEventListener === 'function'
    ) {
        document.addEventListener('DOMContentLoaded', injectDomMarkers);
    }
}
