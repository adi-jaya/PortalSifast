import type { LucideIcon } from 'lucide-react';
import { FileStack, ListTodo, PlusCircle } from 'lucide-react';

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

export function buildTatanaskahNavGroup(canView?: boolean): NavGroup | null {
    if (!canView) {
        return null;
    }

    return {
        id: 'tatanaskah',
        label: 'Tata Naskah',
        icon: FileStack,
        items: [
            {
                id: 'tatanaskah-dokumen',
                label: 'Naskah Dinas Arahan',
                href: '/tatanaskah/dokumen',
                icon: ListTodo,
                isActive: (path) =>
                    path === '/tatanaskah/dokumen' ||
                    path.startsWith('/tatanaskah/dokumen?') ||
                    /^\/tatanaskah\/dokumen\/\d+/.test(path),
            },
            {
                id: 'tatanaskah-create',
                label: 'Buat Dokumen',
                href: '/tatanaskah/dokumen/create',
                icon: PlusCircle,
                isActive: (path) => path === '/tatanaskah/dokumen/create',
            },
        ],
    };
}
