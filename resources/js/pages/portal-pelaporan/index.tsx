import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Globe,
    Layers,
    Search,
    ShieldCheck,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { ExtensionGuideBanner } from '@/components/portal/extension-guide-banner';
import { ExtensionInstallDialog } from '@/components/portal/extension-install-dialog';
import { ExtensionStatusBadge } from '@/components/portal/extension-status-badge';
import { PersonalCredentialDialog } from '@/components/portal/personal-credential-dialog';
import { PortalCard } from '@/components/portal/portal-card';
import { useExtensionDetection } from '@/components/portal/use-extension-detection';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, SharedData } from '@/types';
import type { PortalCardItem } from '@/types/portal';

interface Props {
    portals: PortalCardItem[];
    categories: string[];
    filters: {
        category: string;
        search: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Portal Pelaporan Eksternal', href: '/portal-pelaporan' },
];

export default function PortalPelaporanIndex({
    portals,
    categories,
    filters,
}: Props) {
    const { permissions } = usePage<SharedData>().props;
    const extensionStatus = useExtensionDetection();

    const [isInstallGuideOpen, setIsInstallGuideOpen] = useState(false);
    const [selectedPortalForCredentials, setSelectedPortalForCredentials] = useState<PortalCardItem | null>(null);
    const [isCredentialModalOpen, setIsCredentialModalOpen] = useState(false);

    // Live Client-side Filter
    const [selectedCategory, setSelectedCategory] = useState<string>(filters.category || 'all');
    const [searchTerm, setSearchTerm] = useState<string>(filters.search || '');

    // Hitung portal yang memenuhi filter pencarian dan kategori
    const filteredPortals = useMemo(() => {
        return portals.filter((portal) => {
            const matchesCategory =
                selectedCategory === 'all' || portal.category.toLowerCase() === selectedCategory.toLowerCase();

            const query = searchTerm.toLowerCase().trim();
            const matchesSearch =
                !query ||
                portal.name.toLowerCase().includes(query) ||
                (portal.description && portal.description.toLowerCase().includes(query)) ||
                portal.category.toLowerCase().includes(query);

            return matchesCategory && matchesSearch;
        });
    }, [portals, selectedCategory, searchTerm]);

    // Hitung jumlah portal per kategori untuk lencana pill
    const categoryCounts = useMemo(() => {
        const counts: Record<string, number> = { all: portals.length };
        for (const portal of portals) {
            const key = portal.category.toLowerCase().trim();
            counts[key] = (counts[key] || 0) + 1;
        }
        return counts;
    }, [portals]);

    const handleOpenPersonalCredentialModal = (portal: PortalCardItem) => {
        setSelectedPortalForCredentials(portal);
        setIsCredentialModalOpen(true);
    };

    const handleCredentialSaved = () => {
        // Muat ulang data Inertia agar status kredensial kartu terbarui
        router.reload({ only: ['portals'] });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Portal Pelaporan Eksternal" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header Utama */}
                <div className="flex flex-col justify-between gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-center dark:border-slate-800">
                    <div>
                        <div className="flex items-center gap-2.5">
                            <Heading
                                title="Portal Pelaporan Eksternal"
                                description="Akses terpadu ke portal pelaporan resmi pemerintah (Kemenkes & BKKBN) dengan pengisian login otomatis yang aman."
                            />
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {/* Extension Status Badge */}
                        <ExtensionStatusBadge
                            status={extensionStatus}
                            onOpenGuide={() => setIsInstallGuideOpen(true)}
                        />

                        {/* Admin Action Links */}
                        {permissions?.can_manage_portals && (
                            <div className="flex items-center gap-1.5 pl-2 border-l border-slate-200 dark:border-slate-800">
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="h-8 gap-1.5 text-xs"
                                >
                                    <Link href="/admin/portals">
                                        <Globe className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                        <span>Master Portal</span>
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="h-8 gap-1.5 text-xs"
                                >
                                    <Link href="/admin/portals/mapping">
                                        <ShieldCheck className="h-3.5 w-3.5 text-teal-600 dark:text-teal-400" />
                                        <span>Mapping Akses</span>
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Banner Panduan jika Ekstensi belum terpasang */}
                <ExtensionGuideBanner
                    isInstalled={extensionStatus.isInstalled}
                    isChecking={extensionStatus.isChecking}
                    onOpenGuide={() => setIsInstallGuideOpen(true)}
                />

                {/* Filter & Toolbar Area */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    {/* Category Filter Pills */}
                    <div className="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            onClick={() => setSelectedCategory('all')}
                            className={cn(
                                'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                selectedCategory === 'all'
                                    ? 'bg-emerald-600 text-white shadow-2xs'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                            )}
                        >
                            <span>Semua Portal</span>
                            <span
                                className={cn(
                                    'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                                    selectedCategory === 'all'
                                        ? 'bg-emerald-700/80 text-white'
                                        : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                )}
                            >
                                {categoryCounts.all || 0}
                            </span>
                        </button>

                        {categories.map((category) => (
                            <button
                                key={category}
                                type="button"
                                onClick={() => setSelectedCategory(category)}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                    selectedCategory.toLowerCase() === category.toLowerCase()
                                        ? 'bg-emerald-600 text-white shadow-2xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                                )}
                            >
                                <span>{category}</span>
                                <span
                                    className={cn(
                                        'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                                        selectedCategory.toLowerCase() === category.toLowerCase()
                                            ? 'bg-emerald-700/80 text-white'
                                            : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                    )}
                                >
                                    {categoryCounts[category.toLowerCase().trim()] || 0}
                                </span>
                            </button>
                        ))}
                    </div>

                    {/* Live Search Input */}
                    <div className="relative w-full sm:w-72">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            type="text"
                            placeholder="Cari nama atau deskripsi portal..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            aria-label="Cari portal pelaporan"
                            className="h-9 pl-9 pr-8 text-xs"
                        />
                        {searchTerm && (
                            <button
                                type="button"
                                onClick={() => setSearchTerm('')}
                                aria-label="Hapus pencarian"
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                </div>

                {/* Grid Kartu Portal */}
                {filteredPortals.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filteredPortals.map((portal) => (
                            <PortalCard
                                key={portal.id}
                                portal={portal}
                                isExtensionInstalled={extensionStatus.isInstalled}
                                onOpenPersonalModal={handleOpenPersonalCredentialModal}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex min-h-[320px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                        {portals.length === 0 ? (
                            <EmptyState
                                icon={<Layers className="size-8" />}
                                title="Belum Ada Akses Portal"
                                description="Akun Anda belum memiliki izin akses ke portal pelaporan eksternal. Silakan hubungi Tim IT atau Administrator untuk mendapatkan penugasan portal."
                            />
                        ) : (
                            <EmptyState
                                icon={<Search className="size-8" />}
                                title="Tidak Ada Portal Ditemukan"
                                description="Tidak ada portal yang cocok dengan kata kunci atau filter kategori yang dipilih."
                                action={
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setSelectedCategory('all');
                                            setSearchTerm('');
                                        }}
                                    >
                                        Reset Filter
                                    </Button>
                                }
                            />
                        )}
                    </div>
                )}
            </div>

            {/* Modal Dialog Panduan Instalasi Ekstensi */}
            <ExtensionInstallDialog
                open={isInstallGuideOpen}
                onOpenChange={setIsInstallGuideOpen}
            />

            {/* Modal Dialog Pengaturan Kredensial Pribadi */}
            <PersonalCredentialDialog
                key={selectedPortalForCredentials?.id ?? 'none'}
                portal={selectedPortalForCredentials}
                open={isCredentialModalOpen}
                onOpenChange={setIsCredentialModalOpen}
                onSuccess={handleCredentialSaved}
            />
        </AppLayout>
    );
}
