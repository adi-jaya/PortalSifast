import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Columns3,
    FilePenLine,
    FileText,
    FolderCog,
    LayoutGrid,
    ListFilter,
    Package,
    Server,
    Settings2,
    Ticket,
    UserCircle,
    Users,
    Wifi,
} from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { PortalNavPermissions } from '@/lib/portal-nav';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tiket',
        href: '/tickets',
        icon: Ticket,
    },
    {
        title: 'Draf Saya',
        href: '/tickets?draft=1',
        icon: FilePenLine,
    },
    {
        title: 'Papan Tiket',
        href: '/tickets/board',
        icon: Columns3,
    },
    {
        title: 'Status Tiket',
        href: '/tickets/statuses',
        icon: ListFilter,
    },
    {
        title: 'Catatan Kerja',
        href: '/catatan',
        icon: FileText,
    },
    {
        title: 'Laporan',
        href: '/reports',
        icon: BarChart3,
    },
    {
        title: 'Daftar Pegawai',
        href: '/pegawai',
        icon: UserCircle,
    },
    {
        title: 'Aset',
        href: '/aset',
        icon: Package,
    },
    {
        title: 'User Online',
        href: '/users/online',
        icon: Wifi,
    },
    {
        title: 'Daftar User',
        href: '/users',
        icon: Users,
    },
];

const monitoringNavItems: NavItem[] = [
    {
        title: 'Perangkat',
        href: '/monitoring',
        icon: Activity,
    },
    {
        title: 'Kategori Monitor',
        href: '/monitoring/pengaturan-kategori',
        icon: Settings2,
    },
    {
        title: 'Kesehatan Infrastruktur',
        href: '/infrastruktur',
        icon: Server,
    },
];

const settingsNavItems: NavItem[] = [
    {
        title: 'Master Tiket',
        href: '/settings/tickets',
        icon: FolderCog,
    },
];

export function AppSidebar() {
    const { permissions } = usePage<{ permissions?: PortalNavPermissions }>().props;
    const visibleMainNavItems = mainNavItems.filter(
        (item) => item.href !== '/aset' || Boolean(permissions?.can_access_aset),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleMainNavItems} />
                <NavMain items={monitoringNavItems} label="Monitoring" />
                <NavMain items={settingsNavItems} label="Pengaturan" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
