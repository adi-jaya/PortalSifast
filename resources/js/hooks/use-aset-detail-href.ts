import { usePage } from '@inertiajs/react';
import type { PortalNavPermissions } from '@/lib/portal-nav';

/**
 * Pengguna tanpa akses modul aset diarahkan ke halaman scan publik (/q/{kode}).
 */
export function useAsetDetailHref(): (kodeAset: string) => string {
    const { permissions } = usePage<{ permissions?: PortalNavPermissions }>().props;
    const canAccessAset = Boolean(permissions?.can_access_aset);

    return (kodeAset: string) =>
        canAccessAset ? `/aset/${encodeURIComponent(kodeAset)}` : `/q/${encodeURIComponent(kodeAset)}`;
}
