import type { LucideIcon } from 'lucide-react';
import { BedDouble, FileText, Handshake, Instagram, LayoutGrid, MessageSquareWarning, Newspaper, Stethoscope, TicketPercent } from 'lucide-react';

export type WebOfficialNavPermissions = {
    can_manage?: boolean;
};

export type NavItem = {
    id: string;
    label: string;
    href: string;
    icon: LucideIcon;
    isActive: (path: string) => boolean;
};

export type NavGroup = {
    id: string;
    label: string;
    icon: LucideIcon;
    items: NavItem[];
};

export function buildWebOfficialNavGroup(perm?: WebOfficialNavPermissions): NavGroup | null {
    if (!perm?.can_manage) {
        return null;
    }

    return {
        id: 'web-official',
        label: 'Website Official',
        icon: LayoutGrid,
        items: [
            {
                id: 'web-official-dashboard',
                label: 'Dashboard',
                href: '/web-official',
                icon: LayoutGrid,
                isActive: (path) => path === '/web-official',
            },
            {
                id: 'web-official-articles',
                label: 'Berita & Informasi',
                href: '/web-official/articles',
                icon: FileText,
                isActive: (path) => path.startsWith('/web-official/articles'),
            },
            {
                id: 'web-official-rooms',
                label: 'Kamar Inap',
                href: '/web-official/rooms',
                icon: BedDouble,
                isActive: (path) => path.startsWith('/web-official/rooms'),
            },
            {
                id: 'web-official-promosi',
                label: 'Promosi',
                href: '/web-official/promosi',
                icon: TicketPercent,
                isActive: (path) => path.startsWith('/web-official/promosi'),
            },
            {
                id: 'web-official-poliklinik',
                label: 'Poliklinik',
                href: '/web-official/poliklinik',
                icon: Stethoscope,
                isActive: (path) => path.startsWith('/web-official/poliklinik'),
            },
            {
                id: 'web-official-rekanan',
                label: 'Rekanan',
                href: '/web-official/rekanan',
                icon: Handshake,
                isActive: (path) => path.startsWith('/web-official/rekanan'),
            },
            {
                id: 'web-official-kritik-saran',
                label: 'Kritik & Saran',
                href: '/web-official/kritik-saran',
                icon: MessageSquareWarning,
                isActive: (path) => path.startsWith('/web-official/kritik-saran'),
            },
            {
                id: 'web-official-instagram',
                label: 'Instagram',
                href: '/web-official/instagram',
                icon: Instagram,
                isActive: (path) => path.startsWith('/web-official/instagram'),
            },
            {
                id: 'web-official-berita-eksternal',
                label: 'Berita Eksternal',
                href: '/web-official/berita-eksternal',
                icon: Newspaper,
                isActive: (path) => path.startsWith('/web-official/berita-eksternal'),
            },
        ],
    };
}
