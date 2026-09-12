import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Boxes,
    Building2,
    Columns3,
    FilePenLine,
    FileText,
    FolderCog,
    Globe,
    LayoutGrid,
    ListFilter,
    MapPin,
    Package,
    Server,
    Settings2,
    ShieldCheck,
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
import { dashboard } from '@/routes';
import type { NavItem, SharedData } from '@/types';
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
        title: 'Inventaris SIMRS',
        href: '/inventaris',
        icon: Package,
    },
    {
        title: 'Master Barang',
        href: '/inventaris-barang',
        icon: Boxes,
    },
    {
        title: 'Master Ruang',
        href: '/inventaris-ruang',
        icon: MapPin,
    },
    {
        title: 'Master Produsen',
        href: '/inventaris-produsen',
        icon: Building2,
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

const portalNavItems: NavItem[] = [
    {
        title: 'Master Portal',
        href: '/admin/portals',
        icon: Globe,
    },
    {
        title: 'Mapping Akses',
        href: '/admin/portals/mapping',
        icon: ShieldCheck,
    },
];

export function AppSidebar() {
    const { permissions } = usePage<SharedData>().props;

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
                <NavMain items={mainNavItems} />
                {permissions?.can_manage_portals && (
                    <NavMain items={portalNavItems} label="Portal Eksternal" />
                )}
                <NavMain items={monitoringNavItems} label="Monitoring" />
                <NavMain items={settingsNavItems} label="Pengaturan" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

export default AppSidebar;
