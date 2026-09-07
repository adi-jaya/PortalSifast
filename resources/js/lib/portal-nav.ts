import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    AlertTriangle,
    ArrowLeftRight,
    BadgeCheck,
    Bell,
    Building2,
    ClipboardCheck,
    Columns3,
    FileText,
    FolderCog,
    FolderKanban,
    HandCoins,
    HeartPulse,
    Inbox,
    LayoutGrid,
    ListFilter,
    ListTodo,
    MapPin,
    MessageCircle,
    Package,
    PlusCircle,
    RefreshCw,
    Settings,
    Settings2,
    Server,
    Shapes,
    Shield,
    Tags,
    UserCircle,
    Users,
    Wallet,
    Boxes,
    BarChart3,
    Car,
    FileStack,
} from 'lucide-react';
import { buildSikatNavGroup } from '@/lib/build-sikat-nav-group';
import { buildSimmutuNavGroup } from '@/lib/build-simmutu-nav-group';
import { buildTatanaskahNavGroup } from '@/lib/build-tatanaskah-nav-group';
import { buildWebOfficialNavGroup } from '@/lib/build-web-official-nav-group';
import { dashboard } from '@/routes';

export const APP_NAME = 'Portal Sifast';
export const APP_SUBTITLE = 'RS Aisyiyah Siti Fatimah';

export type PortalNavItem = {
    id: string;
    label: string;
    href: string;
    icon: LucideIcon;
    isActive: (path: string) => boolean;
    fullPage?: boolean;
};

export type PortalNavGroup = {
    id: string;
    label: string;
    icon: LucideIcon;
    hint?: string;
    items: PortalNavItem[];
};

export type PortalNavPermissions = {
    can_access_payroll?: boolean;
    can_access_patroli?: boolean;
    can_access_checklist_kendaraan?: boolean;
    can_create_driver_pemeriksaan?: boolean;
    can_coordinate_checklist_kendaraan?: boolean;
    can_manage_driver_master?: boolean;
    can_access_monitoring?: boolean;
    can_manage_monitoring_kategori?: boolean;
    can_access_berkas_kepegawaian?: boolean;
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

function isTicketListActive(path: string): boolean {
    return path === '/tickets' || /^\/tickets\/\d+/.test(path) || /^\/tickets\/\d+\/edit/.test(path);
}

function isTicketCreateActive(path: string): boolean {
    return path === '/tickets/create';
}

export const mainNavItems: PortalNavItem[] = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        href: dashboard().url,
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

export const settingsNavItems: PortalNavItem[] = [
    {
        id: 'profile',
        label: 'Profil',
        href: '/settings/profile',
        icon: Settings,
        isActive: (path) => path.startsWith('/settings/profile'),
    },
    {
        id: 'tickets-master',
        label: 'Master Tiket',
        href: '/settings/tickets',
        icon: FolderCog,
        isActive: (path) => path.startsWith('/settings/tickets'),
    },
];

export const moduleGroups: PortalNavGroup[] = [
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
        id: 'patroli',
        label: 'Patroli',
        icon: Shield,
        items: [
            {
                id: 'patroli-checkin',
                label: 'Check-in',
                href: '/patroli/checkin',
                icon: ClipboardCheck,
                isActive: (path) =>
                    path === '/patroli/checkin' ||
                    /^\/patroli\/checkin\/\d+/.test(path) ||
                    path.startsWith('/patroli/scan/'),
            },
            {
                id: 'patroli-laporan',
                label: 'Laporan',
                href: '/patroli/laporan',
                icon: BarChart3,
                isActive: (path) => path.startsWith('/patroli/laporan'),
            },
            {
                id: 'patroli-templates',
                label: 'Template',
                href: '/patroli/templates',
                icon: ListTodo,
                isActive: (path) => path.startsWith('/patroli/templates'),
            },
            {
                id: 'patroli-area',
                label: 'Area & Ruang',
                href: '/patroli/area',
                icon: MapPin,
                isActive: (path) => path.startsWith('/patroli/area') || path.startsWith('/patroli/titik'),
            },
        ],
    },
    {
        id: 'driver',
        label: 'Driver',
        icon: Car,
        hint: 'Checklist kendaraan',
        items: [
            {
                id: 'driver-dashboard',
                label: 'Checklist Hari Ini',
                href: '/driver',
                icon: ClipboardCheck,
                isActive: (path) => path === '/driver',
            },
            {
                id: 'driver-pemeriksaan',
                label: 'Pemeriksaan',
                href: '/driver/pemeriksaan',
                icon: ListTodo,
                isActive: (path) => path.startsWith('/driver/pemeriksaan'),
            },
            {
                id: 'driver-riwayat',
                label: 'Riwayat',
                href: '/driver/riwayat',
                icon: FileText,
                isActive: (path) => path.startsWith('/driver/riwayat'),
            },
            {
                id: 'driver-laporan',
                label: 'Laporan',
                href: '/driver/laporan',
                icon: BarChart3,
                isActive: (path) => path.startsWith('/driver/laporan'),
            },
            {
                id: 'driver-kendaraan',
                label: 'Master Kendaraan',
                href: '/driver/kendaraan',
                icon: Car,
                isActive: (path) => path.startsWith('/driver/kendaraan'),
            },
            {
                id: 'driver-item-checklist',
                label: 'Item Checklist',
                href: '/driver/item-checklist',
                icon: Tags,
                isActive: (path) => path.startsWith('/driver/item-checklist'),
            },
        ],
    },
    {
        id: 'monitoring',
        label: 'Monitoring',
        icon: Activity,
        hint: 'RS Agent + Tianji',
        items: [
            {
                id: 'monitoring-list',
                label: 'Perangkat',
                href: '/monitoring',
                icon: Activity,
                isActive: (path) =>
                    path === '/monitoring' ||
                    (/^\/monitoring\/\d+/.test(path) && !path.startsWith('/monitoring/pengaturan')),
            },
            {
                id: 'monitoring-kategori',
                label: 'Kategori Monitor',
                href: '/monitoring/pengaturan-kategori',
                icon: Settings2,
                isActive: (path) => path.startsWith('/monitoring/pengaturan-kategori'),
            },
            {
                id: 'infrastruktur',
                label: 'Kesehatan Infrastruktur',
                href: '/infrastruktur',
                icon: Server,
                isActive: (path) =>
                    path === '/infrastruktur' ||
                    path.startsWith('/infrastruktur') ||
                    path.startsWith('/laporan-tianji'),
            },
        ],
    },
    {
        id: 'berkas-kepegawaian',
        label: 'Berkas Kepegawaian',
        icon: FileStack,
        items: [
            {
                id: 'berkas-kepegawaian-list',
                label: 'Daftar Pegawai',
                href: '/berkas-kepegawaian',
                icon: Users,
                isActive: (path) =>
                    path === '/berkas-kepegawaian' ||
                    (path.startsWith('/berkas-kepegawaian/') &&
                        !path.startsWith('/berkas-kepegawaian/master') &&
                        !path.startsWith('/berkas-kepegawaian/inbox') &&
                        !path.startsWith('/berkas-kepegawaian/referensi')),
            },
            {
                id: 'berkas-kepegawaian-inbox',
                label: 'Inbox Scan',
                href: '/berkas-kepegawaian/inbox',
                icon: Inbox,
                isActive: (path) =>
                    path === '/berkas-kepegawaian/inbox' ||
                    path.startsWith('/berkas-kepegawaian/inbox/'),
            },
            {
                id: 'berkas-kepegawaian-master',
                label: 'Master Jenis Berkas',
                href: '/berkas-kepegawaian/master',
                icon: FolderCog,
                isActive: (path) =>
                    path === '/berkas-kepegawaian/master' ||
                    path.startsWith('/berkas-kepegawaian/master/'),
            },
            {
                id: 'berkas-kepegawaian-referensi',
                label: 'Master Referensi',
                href: '/berkas-kepegawaian/referensi',
                icon: ListFilter,
                isActive: (path) =>
                    path === '/berkas-kepegawaian/referensi' ||
                    path.startsWith('/berkas-kepegawaian/referensi/'),
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
                id: 'aset-master-kategori',
                label: 'Master Kategori',
                href: '/aset/master/kategori',
                icon: FolderKanban,
                isActive: (path) => path.startsWith('/aset/master/kategori'),
            },
            {
                id: 'aset-master-jenis',
                label: 'Master Jenis',
                href: '/aset/master/jenis',
                icon: Shapes,
                isActive: (path) => path.startsWith('/aset/master/jenis'),
            },
            {
                id: 'aset-master-merk',
                label: 'Master Merk',
                href: '/aset/master/merk',
                icon: BadgeCheck,
                isActive: (path) => path.startsWith('/aset/master/merk'),
            },
            {
                id: 'aset-master-ruang',
                label: 'Master Ruang',
                href: '/aset/master/ruang',
                icon: MapPin,
                isActive: (path) => path.startsWith('/aset/master/ruang'),
            },
            {
                id: 'aset-non-alkes',
                label: 'Katalog Non-Alkes',
                href: '/aset/master/non-alkes',
                icon: Tags,
                isActive: (path) => path.startsWith('/aset/master/non-alkes'),
            },
            {
                id: 'aset-aspak',
                label: 'Katalog ASPAK',
                href: '/aset/master/aspak',
                icon: HeartPulse,
                isActive: (path) => path.startsWith('/aset/master/aspak'),
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

export function buildVisibleModuleGroups(permissions?: PortalNavPermissions): PortalNavGroup[] {
    const canAccessPayroll = Boolean(permissions?.can_access_payroll);
    const canAccessPatroli = Boolean(permissions?.can_access_patroli);
    const canCreateDriverPemeriksaan = Boolean(permissions?.can_create_driver_pemeriksaan);
    const canCoordinateDriver = Boolean(permissions?.can_coordinate_checklist_kendaraan);
    const canManageDriverMaster = Boolean(permissions?.can_manage_driver_master);
    const canAccessDriver =
        Boolean(permissions?.can_access_checklist_kendaraan) ||
        canCreateDriverPemeriksaan ||
        canCoordinateDriver ||
        canManageDriverMaster;
    const canAccessMonitoring = Boolean(permissions?.can_access_monitoring);
    const canManageMonitoringKategori = Boolean(permissions?.can_manage_monitoring_kategori);
    const canAccessBerkasKepegawaian = Boolean(permissions?.can_access_berkas_kepegawaian);

    const base = moduleGroups
        .filter((group) => {
            if (group.id === 'payroll') {
                return canAccessPayroll;
            }
            if (group.id === 'patroli') {
                return canAccessPatroli;
            }
            if (group.id === 'driver') {
                return canAccessDriver;
            }
            if (group.id === 'monitoring') {
                return canAccessMonitoring;
            }
            if (group.id === 'berkas-kepegawaian') {
                return canAccessBerkasKepegawaian;
            }

            return true;
        })
        .map((group) => {
            if (group.id === 'driver') {
                return {
                    ...group,
                    items: group.items.filter((item) => {
                        if (item.id === 'driver-pemeriksaan') {
                            return canCreateDriverPemeriksaan;
                        }
                        if (item.id === 'driver-laporan') {
                            return canCoordinateDriver || canManageDriverMaster;
                        }
                        if (item.id === 'driver-kendaraan' || item.id === 'driver-item-checklist') {
                            return canManageDriverMaster;
                        }

                        return true;
                    }),
                };
            }

            if (group.id === 'monitoring') {
                return {
                    ...group,
                    items: group.items.filter((item) => {
                        if (item.id === 'monitoring-kategori') {
                            return canManageMonitoringKategori;
                        }

                        return true;
                    }),
                };
            }

            return group;
        });

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
}
