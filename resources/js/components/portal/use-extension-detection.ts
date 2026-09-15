import { useEffect, useState } from 'react';
import type { ExtensionStatus } from '@/types/portal';

/**
 * Hook untuk mendeteksi keberadaan browser extension SIMRS Sifast.
 *
 * Mendeteksi melalui 3 mekanisme:
 * 1. Pengecekan atribut dataset pada document.documentElement (dataset.sifastExtensionInstalled).
 * 2. Mendengarkan CustomEvent 'SIFAST_EXTENSION_READY' saat ekstensi diinisialisasi.
 * 3. Mengirimkan CustomEvent 'SIFAST_PING_EXTENSION' dan mendengarkan respon 'SIFAST_PONG_EXTENSION'.
 */
export function useExtensionDetection(): ExtensionStatus {
    const [status, setStatus] = useState<ExtensionStatus>({
        isInstalled: false,
        version: null,
        isChecking: true,
    });

    useEffect(() => {
        let isMounted = true;

        const checkDataset = (): boolean => {
            if (typeof document === 'undefined') return false;

            const dataset = document.documentElement.dataset;
            if (dataset.sifastExtensionInstalled === 'true') {
                if (isMounted) {
                    setStatus({
                        isInstalled: true,
                        version: dataset.sifastExtensionVersion || '1.0.0',
                        isChecking: false,
                    });
                }
                return true;
            }
            return false;
        };

        // 1. Cek langsung dataset DOM
        if (checkDataset()) {
            return;
        }

        // 2. Handler event 'SIFAST_EXTENSION_READY' & 'SIFAST_PONG_EXTENSION'
        const handleExtensionSignal = (event: Event) => {
            if (!isMounted) return;

            const customEv = event as CustomEvent<{ version?: string; installed?: boolean }>;
            const version = customEv.detail?.version || document.documentElement.dataset.sifastExtensionVersion || '1.0.0';

            setStatus({
                isInstalled: true,
                version,
                isChecking: false,
            });
        };

        window.addEventListener('SIFAST_EXTENSION_READY', handleExtensionSignal);
        window.addEventListener('SIFAST_PONG_EXTENSION', handleExtensionSignal);

        // 3. Ping ekstensi secara aktif
        try {
            window.dispatchEvent(new CustomEvent('SIFAST_PING_EXTENSION'));
        } catch {
            // Abaikan jika dispatch gagal di lingkungan non-browser
        }

        // 4. Fallback timeout jika ekstensi tidak terpasang (berhenti checking setelah 500ms)
        const timer = setTimeout(() => {
            if (isMounted) {
                // Cek sekali lagi sebelum menyerah
                if (!checkDataset()) {
                    setStatus((prev) => ({
                        ...prev,
                        isChecking: false,
                    }));
                }
            }
        }, 500);

        return () => {
            isMounted = false;
            clearTimeout(timer);
            window.removeEventListener('SIFAST_EXTENSION_READY', handleExtensionSignal);
            window.removeEventListener('SIFAST_PONG_EXTENSION', handleExtensionSignal);
        };
    }, []);

    return status;
}
