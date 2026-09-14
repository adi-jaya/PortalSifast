import type { LucideIcon } from 'lucide-react';
import { Globe, ShieldCheck } from 'lucide-react';

export type NavItem = {
    id: string;
    label: string;
    href: string;
    icon: LucideIcon;
    isActive: (path: string) => boolean;
    fullPage?: boolean;
};

export type NavGroup = {
    id: string;
    label: string;
    icon: LucideIcon;
    items: NavItem[];
};

export function buildPortalNavGroup(canManage?: boolean): NavGroup | null {
    if (!canManage) {
        return null;
    }

    return {
        id: 'portal-eksternal',
        label: 'Portal Eksternal',
        icon: Globe,
        items: [
            {
                id: 'admin-portals',
                label: 'Master Portal',
                href: '/admin/portals',
                icon: Globe,
                isActive: (path) =>
                    !path.startsWith('/admin/portals/mapping') &&
                    (path === '/admin/portals' ||
                        path.startsWith('/admin/portals/') ||
                        path.startsWith('/admin/portals?')),
            },
            {
                id: 'admin-portals-mapping',
                label: 'Mapping Akses',
                href: '/admin/portals/mapping',
                icon: ShieldCheck,
                isActive: (path) =>
                    path === '/admin/portals/mapping' ||
                    path.startsWith('/admin/portals/mapping/') ||
                    path.startsWith('/admin/portals/mapping?'),
            },
        ],
    };
}
