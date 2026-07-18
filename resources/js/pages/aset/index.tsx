import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Box,
    ClipboardCheck,
    ImageOff,
    MapPin,
    Plus,
    RefreshCw,
    Search,
    Ticket,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
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
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type AsetRow = {
    id: number;
    kode_aset: string;
    no_simrs: string | null;
    no_seri: string | null;
    nama_barang: string;
    nama_merk: string | null;
    nama_jenis: string | null;
    nama_ruang: string | null;
    kelas_aset: string | null;
    wajib_kalibrasi: boolean;
    kondisi: string | null;
    status_fungsi: string | null;
    tingkat_kerusakan: string | null;
    siklus_hidup: string;
    tahun_registrasi: number | null;
    photo_src: string | null;
    open_tickets: number;
};

type Props = {
    asets: {
        data: AsetRow[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
        from?: number | null;
        to?: number | null;
        current_page?: number;
        last_page?: number;
    };
    ruang: { id: number; kode_ruang: string; nama_ruang: string }[];
    filters: {
        q?: string;
        kelas_aset?: string;
        siklus_hidup?: string;
        aset_ruang_id?: number | null;
    };
    stats: {
        total: number;
        draf: number;
        aktif: number;
        medis: number;
        non_medis: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Aset', href: '/aset' },
];

function siklusBadge(siklus: string) {
    if (siklus === 'aktif') {
        return 'border-teal-700/20 bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-300';
    }
    if (siklus === 'draf') {
        return 'border-amber-500/25 bg-amber-50 text-amber-900 dark:bg-amber-950/30 dark:text-amber-200';
    }

    return 'bg-muted text-muted-foreground';
}

function Thumb({ src, alt }: { src: string | null; alt: string }) {
    return (
        <div className="relative h-11 w-11 shrink-0 overflow-hidden rounded-md border border-border/70 bg-muted sm:h-12 sm:w-12">
            {src ? (
                <img src={src} alt={alt} className="h-full w-full object-cover" loading="lazy" />
            ) : (
                <div className="flex h-full w-full items-center justify-center text-muted-foreground">
                    <ImageOff className="h-3.5 w-3.5" />
                </div>
            )}
        </div>
    );
}

export default function AsetIndex({ asets, ruang, filters, stats }: Props) {
    const { flash } = usePage().props as { flash?: { success?: string } };
    const [q, setQ] = useState(filters.q ?? '');
    const [kelas, setKelas] = useState(filters.kelas_aset || '__all__');
    const [siklus, setSiklus] = useState(filters.siklus_hidup || '__all__');
    const [ruangId, setRuangId] = useState(
        filters.aset_ruang_id ? String(filters.aset_ruang_id) : '__all__',
    );

    const apply = (overrides: Record<string, string | undefined> = {}) => {
        router.get(
            '/aset',
            {
                q: q || undefined,
                kelas_aset: kelas === '__all__' ? undefined : kelas,
                siklus_hidup: siklus === '__all__' ? undefined : siklus,
                aset_ruang_id: ruangId === '__all__' ? undefined : ruangId,
                ...overrides,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const onSearch = (e: FormEvent) => {
        e.preventDefault();
        apply();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aset Portal" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 px-1 sm:px-0">
                {/* Header */}
                <header className="flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                            Inventaris portal
                        </p>
                        <h1 className="text-[1.65rem] leading-tight font-semibold tracking-tight sm:text-[1.75rem]">
                            Aset
                        </h1>
                        <p className="mt-1 max-w-xl text-sm leading-relaxed text-muted-foreground">
                            Nomor custom, foto lokal, sinkron SIMRS read-only.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/aset/audit">
                                <ClipboardCheck className="mr-1.5 h-3.5 w-3.5" />
                                Audit
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/aset/sinkron">
                                <RefreshCw className="mr-1.5 h-3.5 w-3.5" />
                                Sinkron
                            </Link>
                        </Button>
                        <Button size="sm" asChild className="bg-teal-700 hover:bg-teal-800">
                            <Link href="/aset/create">
                                <Plus className="mr-1.5 h-3.5 w-3.5" />
                                Tambah
                            </Link>
                        </Button>
                    </div>
                </header>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-900 dark:text-emerald-200">
                        {flash.success}
                    </div>
                )}

                {/* Stats — compact strip */}
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                    {[
                        { label: 'Total', value: stats.total, accent: true },
                        { label: 'Aktif', value: stats.aktif },
                        { label: 'Draf', value: stats.draf },
                        { label: 'Medis', value: stats.medis },
                        { label: 'Non-medis', value: stats.non_medis },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className={cn(
                                'rounded-lg border border-border/80 px-3 py-2.5',
                                stat.accent
                                    ? 'bg-teal-50/70 dark:bg-teal-950/25'
                                    : 'bg-card',
                            )}
                        >
                            <p className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                {stat.label}
                            </p>
                            <p className="mt-0.5 text-lg font-semibold tabular-nums tracking-tight sm:text-xl">
                                {stat.value}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Filters */}
                <form
                    onSubmit={onSearch}
                    className="flex flex-col gap-2 rounded-xl border border-border/80 bg-card p-3 shadow-[0_1px_2px_rgba(0,0,0,0.03)] sm:gap-2.5 lg:flex-row lg:items-center"
                >
                    <div className="relative min-w-0 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Cari kode, SIMRS, nama barang..."
                            className="h-10 pl-9"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:flex lg:w-auto">
                        <Select
                            value={kelas}
                            onValueChange={(v) => {
                                setKelas(v);
                                apply({ kelas_aset: v === '__all__' ? undefined : v });
                            }}
                        >
                            <SelectTrigger className="h-10 lg:w-36">
                                <SelectValue placeholder="Kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua kelas</SelectItem>
                                <SelectItem value="medis">Medis</SelectItem>
                                <SelectItem value="non_medis">Non-medis</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={siklus}
                            onValueChange={(v) => {
                                setSiklus(v);
                                apply({ siklus_hidup: v === '__all__' ? undefined : v });
                            }}
                        >
                            <SelectTrigger className="h-10 lg:w-36">
                                <SelectValue placeholder="Siklus" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua siklus</SelectItem>
                                <SelectItem value="draf">Draf</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={ruangId}
                            onValueChange={(v) => {
                                setRuangId(v);
                                apply({ aset_ruang_id: v === '__all__' ? undefined : v });
                            }}
                        >
                            <SelectTrigger className="col-span-2 h-10 sm:col-span-1 lg:w-44">
                                <SelectValue placeholder="Ruang" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua ruang</SelectItem>
                                {ruang.map((r) => (
                                    <SelectItem key={r.id} value={String(r.id)}>
                                        {r.nama_ruang}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" size="sm" className="h-10 bg-teal-700 hover:bg-teal-800 lg:px-4">
                        Cari
                    </Button>
                </form>

                {/* List */}
                {asets.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/80 px-6 py-14 text-center">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                            <Box className="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">Belum ada aset</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Sinkron dari SIMRS atau tambah aset baru di portal.
                            </p>
                        </div>
                        <div className="flex flex-wrap justify-center gap-2 pt-1">
                            <Button size="sm" variant="outline" asChild>
                                <Link href="/aset/sinkron">Sinkron SIMRS</Link>
                            </Button>
                            <Button size="sm" asChild className="bg-teal-700 hover:bg-teal-800">
                                <Link href="/aset/create">Tambah aset</Link>
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                        {/* Desktop header */}
                        <div className="hidden border-b border-border/60 bg-muted/30 px-4 py-2.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase md:grid md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,0.9fr)_auto] md:gap-4">
                            <span>Barang</span>
                            <span>Lokasi</span>
                            <span>Status</span>
                            <span className="text-right">Aksi</span>
                        </div>

                        <ul className="divide-y divide-border/60">
                            {asets.data.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={`/aset/${item.kode_aset}`}
                                        className="group flex flex-col gap-3 px-3 py-3 transition-colors hover:bg-muted/35 sm:px-4 md:grid md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,0.9fr)_auto] md:items-center md:gap-4"
                                    >
                                        {/* Barang + thumb */}
                                        <div className="flex min-w-0 items-start gap-3">
                                            <Thumb src={item.photo_src} alt={item.nama_barang} />
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold tracking-tight group-hover:text-teal-800 dark:group-hover:text-teal-300">
                                                    {item.nama_barang}
                                                </p>
                                                <p className="mt-0.5 font-mono text-[11px] text-muted-foreground sm:text-xs">
                                                    {item.kode_aset}
                                                </p>
                                                <p className="mt-0.5 truncate text-[11px] text-muted-foreground">
                                                    {[item.nama_merk, item.nama_jenis, item.no_seri ? `SN ${item.no_seri}` : null]
                                                        .filter(Boolean)
                                                        .join(' · ') || '—'}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Lokasi */}
                                        <div className="flex min-w-0 items-center gap-1.5 pl-14 text-sm text-muted-foreground md:pl-0">
                                            <MapPin className="h-3.5 w-3.5 shrink-0 opacity-70" />
                                            <span className="truncate">{item.nama_ruang ?? 'Ruang belum diisi'}</span>
                                        </div>

                                        {/* Status */}
                                        <div className="flex flex-wrap items-center gap-1.5 pl-14 md:pl-0">
                                            <Badge
                                                variant="outline"
                                                className={cn('rounded-md px-1.5 py-0 text-[10px] font-medium capitalize', siklusBadge(item.siklus_hidup))}
                                            >
                                                {item.siklus_hidup}
                                            </Badge>
                                            {item.kelas_aset && (
                                                <Badge variant="secondary" className="rounded-md px-1.5 py-0 text-[10px]">
                                                    {item.kelas_aset}
                                                </Badge>
                                            )}
                                            {item.status_fungsi === 'tidak_berfungsi' && (
                                                <Badge variant="destructive" className="rounded-md px-1.5 py-0 text-[10px]">
                                                    Tidak berfungsi
                                                </Badge>
                                            )}
                                            {item.open_tickets > 0 && (
                                                <Badge variant="outline" className="rounded-md px-1.5 py-0 text-[10px]">
                                                    <Ticket className="mr-0.5 h-2.5 w-2.5" />
                                                    {item.open_tickets}
                                                </Badge>
                                            )}
                                        </div>

                                        {/* Aksi hint */}
                                        <div className="hidden text-right text-xs text-muted-foreground md:block">
                                            <span className="opacity-0 transition-opacity group-hover:opacity-100">
                                                Lihat →
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Footer + pagination */}
                <div className="flex flex-col gap-3 border-t border-border/60 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs text-muted-foreground sm:text-sm">
                        {asets.from != null && asets.to != null
                            ? `${asets.from}–${asets.to} dari ${asets.total} aset`
                            : `Total ${asets.total} aset`}
                    </p>
                    {asets.links.length > 3 && (
                        <div className="flex flex-wrap gap-1">
                            {asets.links.map((link, i) => {
                                const label = link.label
                                    .replace('&laquo;', '‹')
                                    .replace('&raquo;', '›')
                                    .replace(/Previous|pagination\.previous/gi, '‹')
                                    .replace(/Next|pagination\.next/gi, '›');

                                if (!link.url) {
                                    return (
                                        <span
                                            key={i}
                                            className="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-muted-foreground/50"
                                            dangerouslySetInnerHTML={{ __html: label }}
                                        />
                                    );
                                }

                                return (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        preserveScroll
                                        preserveState
                                        className={cn(
                                            'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs transition-colors',
                                            link.active
                                                ? 'border-teal-700 bg-teal-700 text-white'
                                                : 'border-border/80 bg-card hover:bg-muted',
                                        )}
                                        dangerouslySetInnerHTML={{ __html: label }}
                                    />
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
