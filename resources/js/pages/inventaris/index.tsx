import { Head, Link, router } from '@inertiajs/react';
import {
    ClipboardCheck,
    Download,
    Search,
    Package,
    Plus,
    Pencil,
    Trash2,
    MapPin,
    Tag,
    QrCode,
    Ticket,
} from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
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
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Inventaris', href: '/inventaris' },
];

type InventarisItem = {
    no_inventaris: string;
    kode_barang: string;
    nama_barang: string;
    nama_ruang: string | null;
    status_barang: string | null;
    asal_barang: string | null;
    harga: number | null;
    has_photo: boolean;
    photo_src: string | null;
    open_tickets: number;
};

type RuangOption = { id_ruang: string; nama_ruang: string };

type PaginatedInventaris = {
    data: InventarisItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Stats = {
    total: number;
    by_status: Record<string, number>;
};

type Props = {
    inventaris: PaginatedInventaris;
    ruang: RuangOption[];
    stats: Stats;
    filters: { q?: string; status?: string; id_ruang?: string };
    statusOptions: string[];
};

function formatCurrency(n: number | null): string | null {
    if (n == null || Number.isNaN(Number(n))) {
        return null;
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(n);
}

function statusTone(status: string | null): string {
    switch (status) {
        case 'Ada':
            return 'bg-emerald-500/15 text-emerald-800 ring-emerald-500/25 dark:text-emerald-200';
        case 'Rusak':
            return 'bg-rose-500/15 text-rose-800 ring-rose-500/25 dark:text-rose-200';
        case 'Hilang':
            return 'bg-amber-500/15 text-amber-900 ring-amber-500/25 dark:text-amber-200';
        case 'Perbaikan':
            return 'bg-sky-500/15 text-sky-900 ring-sky-500/25 dark:text-sky-200';
        case 'Dipinjam':
            return 'bg-violet-500/15 text-violet-900 ring-violet-500/25 dark:text-violet-200';
        default:
            return 'bg-muted text-muted-foreground ring-border';
    }
}

function InventarisThumb({ item }: { item: InventarisItem }) {
    const [failed, setFailed] = useState(false);

    if (!item.photo_src || failed) {
        return (
            <div className="flex h-full w-full flex-col items-center justify-center gap-2 bg-[radial-gradient(circle_at_30%_20%,#d8f3ee_0%,transparent_55%),linear-gradient(160deg,#e8f6f3_0%,#f4f7f6_45%,#edf2f0_100%)] dark:bg-[radial-gradient(circle_at_30%_20%,#134e4a55_0%,transparent_55%),linear-gradient(160deg,#0f1f1d_0%,#152422_100%)]">
                <Package className="size-10 text-teal-700/40 dark:text-teal-200/40" />
                <span className="font-mono text-[10px] tracking-wider text-teal-800/50 uppercase dark:text-teal-100/40">
                    No photo
                </span>
            </div>
        );
    }

    return (
        <img
            src={item.photo_src}
            alt={item.nama_barang}
            loading="lazy"
            decoding="async"
            onError={() => setFailed(true)}
            onLoad={(e) => {
                const img = e.currentTarget;
                // Missing SIMRS files return a 1×1 PNG placeholder (no console 404).
                if (img.naturalWidth <= 1 && img.naturalHeight <= 1) {
                    setFailed(true);
                }
            }}
            className="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
        />
    );
}

export default function InventarisIndex({ inventaris, ruang, stats, filters, statusOptions }: Props) {
    const [search, setSearch] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status || '__all__');
    const [idRuang, setIdRuang] = useState(filters.id_ruang || '__all__');

    const applyFilters = (overrides: Record<string, string | undefined> = {}) => {
        router.get(
            '/inventaris',
            {
                q: search || undefined,
                status: status === '__all__' ? undefined : status,
                id_ruang: idRuang === '__all__' ? undefined : idRuang,
                ...overrides,
            },
            { preserveState: true },
        );
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('__all__');
        setIdRuang('__all__');
        router.get('/inventaris', {}, { preserveState: true });
    };

    const updateStatus = (noInventaris: string, statusBarang: string) => {
        router.patch(
            `/inventaris/${encodeURIComponent(noInventaris)}/status`,
            { status_barang: statusBarang },
            { preserveScroll: true, preserveState: true },
        );
    };

    const hasFilters = !!(filters.q || filters.status || filters.id_ruang);

    const exportUrl = (() => {
        const params = new URLSearchParams();
        if (filters.q) params.set('q', filters.q);
        if (filters.status) params.set('status', filters.status);
        if (filters.id_ruang) params.set('id_ruang', filters.id_ruang);
        const qs = params.toString();
        return qs ? `/inventaris/export?${qs}` : '/inventaris/export';
    })();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Katalog Inventaris" />

            <div className="relative flex flex-col gap-6">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 -top-6 -z-10 h-56 bg-[radial-gradient(ellipse_at_top,_#ccfbf1_0%,_transparent_55%)] opacity-70 dark:bg-[radial-gradient(ellipse_at_top,_#115e5955_0%,_transparent_55%)]"
                />

                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-xs font-semibold tracking-[0.22em] text-teal-700 uppercase dark:text-teal-300">
                            Katalog aset
                        </p>
                        <h1 className="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            Inventaris
                        </h1>
                        <p className="max-w-xl text-sm text-muted-foreground">
                            Jelajahi aset rumah sakit seperti etalase — foto, lokasi, status, dan harga dalam satu kartu.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {filters.id_ruang && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/inventaris/audit?id_ruang=${encodeURIComponent(filters.id_ruang)}`}
                                    >
                                        <ClipboardCheck className="mr-2 h-4 w-4" />
                                        Audit ruang
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <a
                                        href={`/inventaris/label-print-batch?id_ruang=${encodeURIComponent(filters.id_ruang)}&autoprint=1`}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <QrCode className="mr-2 h-4 w-4" />
                                        Cetak semua label ruang ini
                                    </a>
                                </Button>
                            </>
                        )}
                        <Button variant="outline" asChild>
                            <a href={exportUrl}>
                                <Download className="mr-2 h-4 w-4" />
                                Export CSV
                            </a>
                        </Button>
                        <Button className="bg-teal-700 text-white hover:bg-teal-800 dark:bg-teal-600 dark:hover:bg-teal-500" asChild>
                            <Link href="/inventaris/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Tambah aset
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                    <button
                        type="button"
                        onClick={() => {
                            setStatus('__all__');
                            applyFilters({ status: undefined });
                        }}
                        className={cn(
                            'rounded-2xl border px-3 py-3 text-left transition',
                            !filters.status
                                ? 'border-teal-600/40 bg-teal-600/10'
                                : 'border-border bg-card hover:border-teal-600/30',
                        )}
                    >
                        <p className="text-[11px] text-muted-foreground">Total</p>
                        <p className="text-xl font-semibold tabular-nums">{stats.total}</p>
                    </button>
                    {statusOptions.map((opt) => (
                        <button
                            key={opt}
                            type="button"
                            onClick={() => {
                                setStatus(opt);
                                applyFilters({ status: opt });
                            }}
                            className={cn(
                                'rounded-2xl border px-3 py-3 text-left transition',
                                filters.status === opt
                                    ? 'border-teal-600/40 bg-teal-600/10'
                                    : 'border-border bg-card hover:border-teal-600/30',
                            )}
                        >
                            <p className="text-[11px] text-muted-foreground">{opt}</p>
                            <p className="text-xl font-semibold tabular-nums">
                                {stats.by_status[opt] ?? 0}
                            </p>
                        </button>
                    ))}
                </div>

                <form
                    onSubmit={handleSearch}
                    className="sticky top-2 z-10 flex flex-col gap-2 rounded-2xl border border-teal-900/8 bg-background/85 p-3 shadow-sm backdrop-blur-md lg:flex-row dark:border-teal-100/10"
                >
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama, kode, atau no inventaris..."
                            className="border-transparent bg-muted/40 pl-9 focus-visible:ring-teal-600/30"
                        />
                    </div>
                    <Select
                        value={status}
                        onValueChange={(v) => {
                            setStatus(v);
                            applyFilters({ status: v === '__all__' ? undefined : v });
                        }}
                    >
                        <SelectTrigger className="w-full bg-muted/40 lg:w-44">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua status</SelectItem>
                            {statusOptions.map((opt) => (
                                <SelectItem key={opt} value={opt}>
                                    {opt}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={idRuang}
                        onValueChange={(v) => {
                            setIdRuang(v);
                            applyFilters({ id_ruang: v === '__all__' ? undefined : v });
                        }}
                    >
                        <SelectTrigger className="w-full bg-muted/40 lg:w-52">
                            <SelectValue placeholder="Ruang" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">Semua ruang</SelectItem>
                            {ruang.map((r) => (
                                <SelectItem key={r.id_ruang} value={r.id_ruang}>
                                    {r.nama_ruang}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit" className="bg-teal-700 text-white hover:bg-teal-800">
                        Cari
                    </Button>
                    {hasFilters && (
                        <Button type="button" variant="ghost" onClick={clearFilters}>
                            Reset
                        </Button>
                    )}
                </form>

                {!filters.id_ruang && (
                    <p className="text-xs text-muted-foreground">
                        Tip: filter ruang dulu untuk membuka tombol <strong>Audit ruang</strong> dan cetak label massal.
                    </p>
                )}

                {inventaris.data.length === 0 ? (
                    <div className="rounded-3xl border border-dashed bg-card/60">
                        <EmptyState
                            title={hasFilters ? 'Tidak ada hasil' : 'Belum ada inventaris'}
                            description={
                                hasFilters
                                    ? 'Coba ubah kata kunci atau filter.'
                                    : 'Data inventaris diambil dari SIMRS.'
                            }
                            icon={<Package className="size-7" />}
                        />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {inventaris.data.map((item, index) => {
                            const price = formatCurrency(item.harga);

                            return (
                                <article
                                    key={item.no_inventaris}
                                    className="group relative flex flex-col overflow-hidden rounded-3xl border border-teal-950/8 bg-card shadow-[0_10px_30px_-18px_rgba(15,118,110,0.45)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_40px_-20px_rgba(15,118,110,0.55)] dark:border-teal-100/10"
                                    style={{ animationDelay: `${Math.min(index, 11) * 40}ms` }}
                                >
                                    <Link
                                        href={`/inventaris/${item.no_inventaris}`}
                                        className="relative aspect-[4/3] overflow-hidden bg-muted"
                                    >
                                        <InventarisThumb item={item} />
                                        <div className="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/45 to-transparent opacity-80" />
                                        {item.status_barang && (
                                            <span
                                                className={cn(
                                                    'absolute top-3 left-3 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 backdrop-blur-sm',
                                                    statusTone(item.status_barang),
                                                )}
                                            >
                                                {item.status_barang}
                                            </span>
                                        )}
                                        {item.open_tickets > 0 && (
                                            <span className="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-amber-500/90 px-2 py-1 text-[10px] font-semibold text-white backdrop-blur-sm">
                                                <Ticket className="size-3" />
                                                {item.open_tickets}
                                            </span>
                                        )}
                                        <span className="absolute right-3 bottom-3 rounded-md bg-black/45 px-2 py-1 font-mono text-[10px] tracking-wide text-white backdrop-blur-sm">
                                            {item.no_inventaris}
                                        </span>
                                    </Link>

                                    <div className="flex flex-1 flex-col gap-3 p-4">
                                        <div className="space-y-1">
                                            <Link
                                                href={`/inventaris/${item.no_inventaris}`}
                                                className="line-clamp-2 text-base font-semibold tracking-tight text-foreground transition group-hover:text-teal-800 dark:group-hover:text-teal-200"
                                            >
                                                {item.nama_barang}
                                            </Link>
                                            <Link
                                                href={`/inventaris-barang/${item.kode_barang}`}
                                                className="inline-flex items-center gap-1 font-mono text-xs text-muted-foreground hover:text-teal-700 hover:underline"
                                            >
                                                <Tag className="size-3" />
                                                {item.kode_barang}
                                            </Link>
                                        </div>

                                        <div className="mt-auto space-y-3">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0 space-y-1 text-xs text-muted-foreground">
                                                    <p className="flex items-center gap-1 truncate">
                                                        <MapPin className="size-3 shrink-0" />
                                                        {item.nama_ruang ?? 'Ruang belum diisi'}
                                                    </p>
                                                    {item.asal_barang && (
                                                        <p className="truncate">Asal: {item.asal_barang}</p>
                                                    )}
                                                </div>
                                                {price && (
                                                    <p className="shrink-0 text-sm font-semibold text-teal-800 dark:text-teal-200">
                                                        {price}
                                                    </p>
                                                )}
                                            </div>

                                            <Select
                                                value={item.status_barang || undefined}
                                                onValueChange={(v) => updateStatus(item.no_inventaris, v)}
                                            >
                                                <SelectTrigger className="h-9 w-full">
                                                    <SelectValue placeholder="Ubah status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {statusOptions.map((opt) => (
                                                        <SelectItem key={opt} value={opt}>
                                                            {opt}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>

                                            <div className="flex items-center gap-1 border-t border-border/60 pt-3">
                                                <Button variant="secondary" size="sm" className="flex-1" asChild>
                                                    <Link href={`/inventaris/${item.no_inventaris}`}>Detail</Link>
                                                </Button>
                                                <Button variant="ghost" size="icon" asChild>
                                                    <a
                                                        href={`/inventaris/${item.no_inventaris}/label-print`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        title="Cetak label QR 24mm"
                                                    >
                                                        <QrCode className="h-4 w-4" />
                                                    </a>
                                                </Button>
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link href={`/inventaris/${item.no_inventaris}/edit`}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => {
                                                        if (confirm('Hapus inventaris ini?')) {
                                                            router.delete(`/inventaris/${item.no_inventaris}`);
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}

                {inventaris.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        {inventaris.links.map((link, i) => (
                            <span key={i}>
                                {link.url ? (
                                    <Button size="sm" variant={link.active ? 'default' : 'outline'} asChild>
                                        <Link href={link.url} preserveState>
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        </Link>
                                    </Button>
                                ) : (
                                    <span
                                        className="inline-flex size-8 items-center justify-center text-muted-foreground"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )}
                            </span>
                        ))}
                    </div>
                )}

                <p className="text-sm text-muted-foreground">
                    Menampilkan {inventaris.data.length} dari {inventaris.total} aset
                    {hasFilters && ' (terfilter)'}
                </p>
            </div>
        </AppLayout>
    );
}
