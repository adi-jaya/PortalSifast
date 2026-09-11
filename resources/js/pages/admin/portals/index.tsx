import { Head, Link, router } from '@inertiajs/react';
import {
    ExternalLink,
    Globe,
    KeyRound,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    Users,
    X,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { DataTablePagination } from '@/components/data-table-pagination';
import { DataTableToolbar } from '@/components/data-table-toolbar';
import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Portal } from '@/types';

interface PaginatedPortals {
    data: (Portal & {
        has_shared_password?: boolean;
        user_credentials_count: number;
    })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    portals: PaginatedPortals;
    categories: string[];
    filters: {
        search: string;
        category: string;
        status: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Portal Eksternal', href: '/admin/portals' },
];

export default function AdminPortalsIndex({
    portals,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const applyFilters = useCallback(
        (newFilters: Partial<typeof filters>) => {
            router.get(
                '/admin/portals',
                { ...filters, ...newFilters },
                { preserveState: true, replace: true },
            );
        },
        [filters],
    );

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search });
    };

    const clearFilters = () => {
        setSearch('');
        router.get(
            '/admin/portals',
            {},
            { preserveState: true, replace: true },
        );
    };

    const handleToggleActive = (portal: Portal) => {
        router.patch(
            `/admin/portals/${portal.id}/toggle-active`,
            {},
            { preserveScroll: true },
        );
    };

    const handleDelete = (portal: Portal) => {
        if (
            confirm(
                `Hapus portal "${portal.name}" beserta seluruh mapping akses petugasnya?`,
            )
        ) {
            router.delete(`/admin/portals/${portal.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Portal Pelaporan Eksternal" />

            <div className="flex flex-col gap-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Heading
                        title="Master Portal Pelaporan Eksternal"
                        description="Kelola katalog website pelaporan resmi (Kemenkes & BKKBN), akun bersama RS, dan konfigurasi form selector autofill."
                    />

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" className="gap-2">
                            <Link href="/admin/portals/mapping">
                                <ShieldCheck className="size-4 text-emerald-600" />{' '}
                                Mapping Akses
                            </Link>
                        </Button>
                        <Button asChild className="gap-2">
                            <Link href="/admin/portals/create">
                                <Plus className="size-4" /> Tambah Portal
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTableToolbar>
                    <form
                        onSubmit={handleSearch}
                        className="flex min-w-0 flex-1 gap-2"
                    >
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Cari nama portal, kategori, atau URL..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="h-11 pl-9"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            className="h-11"
                        >
                            Cari
                        </Button>
                    </form>

                    <Select
                        value={filters.category || '_all'}
                        onValueChange={(v) =>
                            applyFilters({ category: v === '_all' ? '' : v })
                        }
                    >
                        <SelectTrigger className="h-11 w-[160px]">
                            <SelectValue placeholder="Semua Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Kategori</SelectItem>
                            {categories.map((cat) => (
                                <SelectItem key={cat} value={cat}>
                                    {cat}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.status || '_all'}
                        onValueChange={(v) =>
                            applyFilters({ status: v === '_all' ? '' : v })
                        }
                    >
                        <SelectTrigger className="h-11 w-[140px]">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Semua Status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>

                    {(filters.search || filters.category || filters.status) && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-11"
                            onClick={clearFilters}
                            aria-label="Reset Filter"
                        >
                            <X className="size-4" />
                        </Button>
                    )}
                </DataTableToolbar>

                <div className="data-table overflow-hidden rounded-xl border border-border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-border bg-muted/50 text-xs font-semibold text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Portal Target</th>
                                    <th className="px-4 py-3">
                                        Kebijakan Akun
                                    </th>
                                    <th className="px-4 py-3">
                                        Akun Bersama RS
                                    </th>
                                    <th className="px-4 py-3 text-center">
                                        Petugas
                                    </th>
                                    <th className="px-4 py-3 text-center">
                                        Urutan
                                    </th>
                                    <th className="px-4 py-3 text-center">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {portals.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-10 text-center"
                                        >
                                            <EmptyState
                                                title="Belum Ada Portal"
                                                description="Belum ada website pelaporan yang didaftarkan. Klik tombol di bawah untuk menambah."
                                                action={
                                                    <Button asChild>
                                                        <Link href="/admin/portals/create">
                                                            Tambah Portal
                                                        </Link>
                                                    </Button>
                                                }
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    portals.data.map((portal) => (
                                        <tr
                                            key={portal.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-start gap-3">
                                                    <div className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                                        <Globe className="size-4" />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2 font-semibold text-foreground">
                                                            <span>
                                                                {portal.name}
                                                            </span>
                                                            <Badge
                                                                variant="outline"
                                                                className="px-1.5 py-0 text-[10px]"
                                                            >
                                                                {
                                                                    portal.category
                                                                }
                                                            </Badge>
                                                        </div>
                                                        <a
                                                            href={portal.url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground hover:text-primary"
                                                        >
                                                            <span className="max-w-xs truncate">
                                                                {portal.url}
                                                            </span>
                                                            <ExternalLink className="size-3" />
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        portal.auth_type ===
                                                        'shared'
                                                            ? 'default'
                                                            : portal.auth_type ===
                                                                'personal'
                                                              ? 'secondary'
                                                              : 'outline'
                                                    }
                                                    className="text-xs capitalize"
                                                >
                                                    {portal.auth_type === 'both'
                                                        ? 'Hybrid'
                                                        : portal.auth_type}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-xs">
                                                {portal.shared_username ? (
                                                    <div className="flex items-center gap-1.5 font-mono text-muted-foreground">
                                                        <KeyRound className="size-3 text-amber-500" />
                                                        <span>
                                                            {
                                                                portal.shared_username
                                                            }
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground italic">
                                                        Personal saja
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Link
                                                    href={`/admin/portals/mapping?portal_id=${portal.id}`}
                                                    className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary hover:bg-primary/20"
                                                >
                                                    <Users className="size-3" />
                                                    <span>
                                                        {
                                                            portal.user_credentials_count
                                                        }
                                                    </span>
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-center font-mono text-xs text-muted-foreground">
                                                {portal.sort_order}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleToggleActive(
                                                            portal,
                                                        )
                                                    }
                                                    className={`inline-flex cursor-pointer items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors ${
                                                        portal.is_active
                                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {portal.is_active
                                                        ? '● Aktif'
                                                        : '○ Nonaktif'}
                                                </button>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                    >
                                                        <Link
                                                            href={`/admin/portals/${portal.id}/edit`}
                                                            aria-label={`Edit portal ${portal.name}`}
                                                        >
                                                            <Pencil className="size-3.5" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            handleDelete(portal)
                                                        }
                                                        className="size-8 text-destructive hover:bg-destructive/10"
                                                        aria-label={`Hapus portal ${portal.name}`}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {portals.last_page > 1 && (
                        <DataTablePagination links={portals.links} />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
