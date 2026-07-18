import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeftRight,
    BadgeCheck,
    Bell,
    Building2,
    ChevronDown,
    ClipboardCheck,
    Columns3,
    FileText,
    FolderCog,
    FolderKanban,
    HandCoins,
    LayoutDashboard,
    LayoutGrid,
    ListFilter,
    ListTodo,
    MapPin,
    MessageCircle,
    Package,
    PlusCircle,
    RefreshCw,
    Settings,
    Shapes,
    Tags,
    UserCircle,
    Users,
    Wallet,
    Boxes,
    BarChart3,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import { NavModuleItemLink } from '@/components/nav-module-item-link';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { buildSikatNavGroup } from '@/lib/build-sikat-nav-group';
import { buildSimmutuNavGroup } from '@/lib/build-simmutu-nav-group';
import { buildTatanaskahNavGroup } from '@/lib/build-tatanaskah-nav-group';
import { buildWebOfficialNavGroup } from '@/lib/build-web-official-nav-group';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

const APP_NAME = 'Portal RS Aisyiyah Siti Fatimah';
const APP_SUBTITLE = 'Panel Admin';

type NavItem = {
    id: string;
    label: string;
    href: string;
    icon: LucideIcon;
    isActive: (path: string) => boolean;
    fullPage?: boolean;
};

type NavGroup = {
    id: string;
    label: string;
    icon: LucideIcon;
    hint?: string;
    items: NavItem[];
};

type SharedPageProps = {
    permissions?: {
        can_access_payroll?: boolean;
        simmutu?: {
            can_view?: boolean;
            can_manage?: boolean;
            can_input?: boolean;
        };
        sikat?: {
            enabled?: boolean;
        };
        tatanaskah?: {
            can_view?: boolean;
        };
        web_official?: {
            can_manage?: boolean;
        };
    };
};

const mainNavItems: NavItem[] = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        isActive: (path) => path === '/dashboard' || path === '/',
    },
    {
        id: 'chat',
        label: 'Chat',
        href: '/chat',
        icon: MessageCircle,
        isActive: (path) => path === '/chat' || /^\/chat\/\d+/.test(path),
    },
    {
        id: 'pegawai',
        label: 'Daftar Pegawai',
        href: '/pegawai',
        icon: UserCircle,
        isActive: (path) => path === '/pegawai' || /^\/pegawai\/.+/.test(path),
    },
    {
        id: 'users',
        label: 'Daftar User',
        href: '/users',
        icon: Users,
        isActive: (path) => path === '/users' || /^\/users\/.+/.test(path),
    },
];

const settingsNavItems = [
    { id: 'profile', label: 'Profil', href: '/settings/profile', icon: Settings },
    { id: 'tickets-master', label: 'Master Tiket', href: '/settings/tickets', icon: FolderCog },
];

function isTicketListActive(path: string): boolean {
    return path === '/tickets' || /^\/tickets\/\d+/.test(path) || /^\/tickets\/\d+\/edit/.test(path);
}

function isTicketCreateActive(path: string): boolean {
    return path === '/tickets/create';
}

const moduleGroups: NavGroup[] = [
    {
        id: 'ticketing',
        label: 'Ticketing',
        icon: ListTodo,
        items: [
            { id: 'tickets', label: 'Daftar Tiket', href: '/tickets', icon: ListTodo, isActive: isTicketListActive },
            {
                id: 'tickets-board',
                label: 'Papan Tiket',
                href: '/tickets/board',
                icon: Columns3,
                isActive: (path) => path === '/tickets/board',
            },
            {
                id: 'tickets-statuses',
                label: 'Status Tiket',
                href: '/tickets/statuses',
                icon: ListFilter,
                isActive: (path) => path === '/tickets/statuses',
            },
            {
                id: 'tickets-create',
                label: 'Buat Tiket',
                href: '/tickets/create',
                icon: PlusCircle,
                isActive: isTicketCreateActive,
            },
            {
                id: 'catatan',
                label: 'Catatan Kerja',
                href: '/catatan',
                icon: FileText,
                isActive: (path) => path === '/catatan' || path.startsWith('/catatan?'),
            },
            {
                id: 'projects',
                label: 'Rencana',
                href: '/projects',
                icon: FolderKanban,
                isActive: (path) =>
                    path === '/projects' ||
                    /^\/projects\/\d+/.test(path) ||
                    /^\/projects\/\d+\/edit/.test(path) ||
                    path === '/projects/create',
            },
            {
                id: 'reports',
                label: 'Laporan',
                href: '/reports',
                icon: BarChart3,
                isActive: (path) => path === '/reports' || /^\/reports\/.+/.test(path),
            },
        ],
    },
    {
        id: 'emergency',
        label: 'Emergency',
        icon: AlertTriangle,
        items: [
            {
                id: 'emergency-reports',
                label: 'Daftar Laporan',
                href: '/emergency-reports',
                icon: AlertTriangle,
                isActive: (path) => path === '/emergency-reports' || /^\/emergency-reports\/\d+/.test(path),
            },
            {
                id: 'emergency-reports-create',
                label: 'Buat Laporan',
                href: '/emergency-reports/create',
                icon: PlusCircle,
                isActive: (path) => path === '/emergency-reports/create',
            },
            {
                id: 'panic-staff',
                label: 'Panic Staff',
                href: '/panic-staff',
                icon: Bell,
                isActive: (path) => path === '/panic-staff',
            },
        ],
    },
    {
        id: 'payroll',
        label: 'Payroll',
        icon: Wallet,
        items: [
            {
                id: 'payroll-dashboard',
                label: 'Dashboard Payroll',
                href: '/payroll/dashboard',
                icon: LayoutGrid,
                isActive: (path) => path === '/payroll/dashboard',
            },
            {
                id: 'payroll-list',
                label: 'Data Payroll',
                href: '/payroll',
                icon: Wallet,
                isActive: (path) =>
                    path === '/payroll' ||
                    /^\/payroll\/\d+/.test(path) ||
                    /^\/payroll\/\d+\/edit/.test(path),
            },
            {
                id: 'payroll-import',
                label: 'Import Payroll',
                href: '/payroll/import',
                icon: PlusCircle,
                isActive: (path) => path === '/payroll/import',
            },
            {
                id: 'payroll-import-history',
                label: 'Riwayat Import',
                href: '/payroll/import-history',
                icon: FileText,
                isActive: (path) => path === '/payroll/import-history',
            },
            {
                id: 'payroll-employee-history',
                label: 'Riwayat Pegawai',
                href: '/payroll/employee-history',
                icon: UserCircle,
                isActive: (path) =>
                    path === '/payroll/employee-history' || path.startsWith('/payroll/employee/'),
            },
            {
                id: 'payroll-audit-logs',
                label: 'Audit Log',
                href: '/payroll/audit-logs',
                icon: ListFilter,
                isActive: (path) => path === '/payroll/audit-logs',
            },
        ],
    },
    {
        id: 'inventaris',
        label: 'Inventaris Portal',
        icon: Boxes,
        hint: 'Bisa diubah',
        items: [
            {
                id: 'aset-list',
                label: 'Aset',
                href: '/aset',
                icon: Package,
                isActive: (path) =>
                    (path === '/aset' || path.startsWith('/aset/')) &&
                    !path.startsWith('/aset/sinkron') &&
                    !path.startsWith('/aset/audit') &&
                    !path.startsWith('/aset/master') &&
                    !path.startsWith('/aset/pengaturan-penyusutan'),
            },
            {
                id: 'aset-peminjaman',
                label: 'Peminjaman',
                href: '/aset-peminjaman',
                icon: HandCoins,
                isActive: (path) => path.startsWith('/aset-peminjaman'),
            },
            {
                id: 'aset-mutasi-lokasi',
                label: 'Mutasi Lokasi',
                href: '/aset-mutasi-lokasi',
                icon: ArrowLeftRight,
                isActive: (path) => path.startsWith('/aset-mutasi-lokasi'),
            },
            {
                id: 'aset-audit',
                label: 'Audit Fisik',
                href: '/aset/audit',
                icon: ClipboardCheck,
                isActive: (path) => path.startsWith('/aset/audit'),
            },
            {
                id: 'aset-non-alkes',
                label: 'Katalog Non-Alkes',
                href: '/aset/master/non-alkes',
                icon: Tags,
                isActive: (path) => path.startsWith('/aset/master/non-alkes'),
            },
            {
                id: 'aset-penyusutan',
                label: 'Pengaturan Penyusutan',
                href: '/aset/pengaturan-penyusutan',
                icon: Wallet,
                isActive: (path) => path.startsWith('/aset/pengaturan-penyusutan'),
            },
            {
                id: 'aset-sinkron',
                label: 'Sinkron SIMRS',
                href: '/aset/sinkron',
                icon: RefreshCw,
                isActive: (path) => path.startsWith('/aset/sinkron'),
            },
        ],
    },
    {
        id: 'inventaris-simrs',
        label: 'Referensi SIMRS',
        icon: Package,
        hint: 'Hanya lihat',
        items: [
            {
                id: 'inventaris-list',
                label: 'Katalog Inventaris',
                href: '/inventaris',
                icon: Package,
                isActive: (path) => path === '/inventaris' || /^\/inventaris\/[^/]+/.test(path),
            },
            {
                id: 'inventaris-barang',
                label: 'Barang',
                href: '/inventaris-barang',
                icon: Boxes,
                isActive: (path) => path === '/inventaris-barang' || /^\/inventaris-barang\/[^/]+/.test(path),
            },
            {
                id: 'inventaris-ruang',
                label: 'Ruang',
                href: '/inventaris-ruang',
                icon: MapPin,
                isActive: (path) => path.startsWith('/inventaris-ruang'),
            },
            {
                id: 'inventaris-kategori',
                label: 'Kategori',
                href: '/inventaris-kategori',
                icon: Tags,
                isActive: (path) => path.startsWith('/inventaris-kategori'),
            },
            {
                id: 'inventaris-jenis',
                label: 'Jenis',
                href: '/inventaris-jenis',
                icon: Shapes,
                isActive: (path) => path.startsWith('/inventaris-jenis'),
            },
            {
                id: 'inventaris-merk',
                label: 'Merk',
                href: '/inventaris-merk',
                icon: BadgeCheck,
                isActive: (path) => path.startsWith('/inventaris-merk'),
            },
            {
                id: 'inventaris-produsen',
                label: 'Produsen',
                href: '/inventaris-produsen',
                icon: Building2,
                isActive: (path) => path.startsWith('/inventaris-produsen'),
            },
        ],
    },
];

export function TemplateSidebar() {
    const { isCurrentUrl, currentUrl } = useCurrentUrl();
    const { permissions } = usePage<SharedPageProps>().props;
    const canAccessPayroll = Boolean(permissions?.can_access_payroll);
    const visibleModuleGroups = useMemo(() => {
        const base = moduleGroups.filter((group) => group.id !== 'payroll' || canAccessPayroll);
        const sikatGroup = buildSikatNavGroup(permissions?.sikat?.enabled);
        if (sikatGroup) {
            base.push(sikatGroup);
        }
        const simmutuGroup = buildSimmutuNavGroup(permissions?.simmutu);
        if (simmutuGroup) {
            base.push(simmutuGroup);
        }
        const tatanaskahGroup = buildTatanaskahNavGroup(permissions?.tatanaskah?.can_view);
        if (tatanaskahGroup) {
            base.push(tatanaskahGroup);
        }
        const webOfficialGroup = buildWebOfficialNavGroup(permissions?.web_official);
        if (webOfficialGroup) {
            base.push(webOfficialGroup);
        }
        return base;
    }, [canAccessPayroll, permissions?.simmutu, permissions?.sikat?.enabled, permissions?.tatanaskah?.can_view, permissions?.web_official]);
    const activeModuleIds = useMemo(
        () =>
            visibleModuleGroups
                .filter((group) => group.items.some((item) => item.isActive(currentUrl)))
                .map((group) => group.id),
        [currentUrl, visibleModuleGroups],
    );
    const [expandedModuleIds, setExpandedModuleIds] = useState<Record<string, boolean>>({});

    function toggleModule(moduleId: string): void {
        setExpandedModuleIds((prev) => ({
            ...prev,
            [moduleId]: !(prev[moduleId] ?? activeModuleIds.includes(moduleId)),
        }));
    }

    return (
        <aside className="fixed left-0 top-0 z-30 hidden h-screen w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground md:flex">
            <div className="border-b border-sidebar-border p-6">
                <Link
                    href={dashboard()}
                    prefetch
                    className="flex items-center gap-3"
                >
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary">
                        <LayoutDashboard className="h-5 w-5 text-sidebar-primary-foreground" />
                    </div>
                    <div className="min-w-0">
                        <h1 className="truncate text-sm font-semibold text-sidebar-primary-foreground">
                            {APP_NAME}
                        </h1>
                        <p className="truncate text-[11px] text-sidebar-muted">
                            {APP_SUBTITLE}
                        </p>
                    </div>
                </Link>
            </div>

            <nav className="flex-1 space-y-6 overflow-y-auto p-4">
                <div className="space-y-1">
                    <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                        Menu Utama
                    </p>
                    {mainNavItems.map((item) => {
                        const isActive = item.isActive(currentUrl);
                        return (
                            <Link
                                key={item.id}
                                href={item.href}
                                prefetch
                                className={cn(
                                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                                    isActive
                                        ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                        : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
                                )}
                            >
                                <item.icon className="h-4 w-4 shrink-0" />
                                {item.label}
                            </Link>
                        );
                    })}
                </div>

                <div className="space-y-1">
                    <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                        Modul
                    </p>
                    {visibleModuleGroups.map((group) => {
                        const hasActiveChild = group.items.some((item) => item.isActive(currentUrl));
                        const isExpanded = expandedModuleIds[group.id] ?? hasActiveChild;

                        return (
                            <div key={group.id} className="space-y-1">
                                <button
                                    type="button"
                                    onClick={() => toggleModule(group.id)}
                                    className={cn(
                                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-colors',
                                        hasActiveChild
                                            ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                            : 'text-sidebar-foreground/80 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
                                    )}
                                >
                                    <group.icon className="h-4 w-4 shrink-0" />
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate">{group.label}</span>
                                        {group.hint ? (
                                            <span className="block truncate text-[10px] font-normal text-sidebar-muted">
                                                {group.hint}
                                            </span>
                                        ) : null}
                                    </span>
                                    <ChevronDown
                                        className={cn(
                                            'h-4 w-4 shrink-0 transition-transform',
                                            isExpanded ? 'rotate-180' : '',
                                        )}
                                    />
                                </button>

                                {isExpanded && (
                                    <div className="space-y-1 pl-4">
                                        {group.items.map((item) => {
                                            const isItemActive = item.isActive(currentUrl);
                                            return (
                                                <NavModuleItemLink
                                                    key={item.id}
                                                    href={item.href}
                                                    icon={item.icon}
                                                    label={item.label}
                                                    isActive={isItemActive}
                                                    fullPage={item.fullPage}
                                                />
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                <div className="space-y-1">
                    <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                        Pengaturan
                    </p>
                    {settingsNavItems.map((item) => (
                        <Link
                            key={item.id}
                            href={item.href}
                            prefetch
                            className={cn(
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                                isCurrentUrl(item.href)
                                    ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
                            )}
                        >
                            <item.icon className="h-4 w-4 shrink-0" />
                            {item.label}
                        </Link>
                    ))}
                </div>
            </nav>
        </aside>
    );
}
