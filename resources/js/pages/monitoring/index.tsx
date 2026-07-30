import { Head, Link, router, usePoll } from '@inertiajs/react';
import { Activity, AlertTriangle, Link2, RefreshCw, Search, Settings2, Unlink, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { MetricBar } from '@/components/monitoring/metric-bar';
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
import { formatRelativeId } from '@/lib/monitoring';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type DeviceRow = {
    id: number;
    uuid: string;
    hostname: string | null;
    computer_name: string | null;
    ip_address: string | null;
    status: 'online' | 'offline';
    last_seen_at: string | null;
    last_cpu_percent: string | number | null;
    last_ram_percent: string | number | null;
    last_disk_percent: string | number | null;
    hardware?: {
        os: string | null;
        os_version: string | null;
        serial_number: string | null;
    } | null;
    aset?: {
        id: number;
        kode_aset: string;
        no_seri: string | null;
    } | null;
};

type Props = {
    devices: {
        data: DeviceRow[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: {
        q?: string;
        status?: string;
        sort?: string;
        aset_link?: string;
    };
    stats: {
        total: number;
        online: number;
        offline: number;
        high_load: number;
        unlinked: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Monitoring', href: '/monitoring' },
];

export default function MonitoringIndex({ devices, filters, stats }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [sort, setSort] = useState(filters.sort || 'last_seen');
    const [asetLink, setAsetLink] = useState(filters.aset_link || 'all');

    usePoll(30000, {
        only: ['devices', 'stats'],
    });

    function applyFilters(next?: { status?: string; sort?: string; aset_link?: string }) {
        const nextStatus = next?.status ?? status;
        const nextSort = next?.sort ?? sort;
        const nextAsetLink = next?.aset_link ?? asetLink;

        router.get(
            '/monitoring',
            {
                q: q || undefined,
                status: nextStatus === 'all' ? undefined : nextStatus,
                sort: nextSort === 'last_seen' ? undefined : nextSort,
                aset_link: nextAsetLink === 'all' ? undefined : nextAsetLink,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        applyFilters();
    }

    function clearFilters() {
        setQ('');
        setStatus('all');
        setSort('last_seen');
        setAsetLink('all');
        router.get('/monitoring', {}, { preserveState: true });
    }

    const hasFilters = Boolean(filters.q || filters.status || filters.sort || filters.aset_link);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monitoring" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                            Operasional IT
                        </p>
                        <h1 className="flex items-center gap-2 text-[1.65rem] leading-tight font-semibold tracking-tight sm:text-[1.75rem]">
                            <Activity className="size-6 text-teal-700 dark:text-teal-400" />
                            Monitoring perangkat
                        </h1>
                        <p className="mt-1 max-w-xl text-sm leading-relaxed text-muted-foreground">
                            Status live dari RS Agent. Hubungkan ke inventaris agar hardware dan aset satu alur kerja.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="gap-1.5 font-normal">
                            <span className="size-1.5 animate-pulse rounded-full bg-emerald-500" />
                            Live · 30 detik
                        </Badge>
                        <Button type="button" variant="outline" size="sm" asChild>
                            <Link href="/monitoring/pengaturan-kategori">
                                <Settings2 className="size-3.5" />
                                Kategori monitor
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload({ only: ['devices', 'stats'] })}
                        >
                            <RefreshCw className="size-3.5" />
                            Refresh
                        </Button>
                    </div>
                </header>

                {(stats.offline > 0 || stats.high_load > 0 || stats.unlinked > 0) && (
                    <div className="flex flex-wrap gap-2">
                        {stats.offline > 0 ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setStatus('offline');
                                    applyFilters({ status: 'offline' });
                                }}
                                className="flex cursor-pointer items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-left text-sm text-amber-900 transition-colors hover:bg-amber-500/15 dark:text-amber-200"
                            >
                                <AlertTriangle className="size-4 shrink-0" />
                                <span>
                                    <strong>{stats.offline}</strong> offline — filter
                                </span>
                            </button>
                        ) : null}
                        {stats.high_load > 0 ? (
                            <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-800 dark:text-red-200">
                                <AlertTriangle className="size-4 shrink-0" />
                                <span>
                                    <strong>{stats.high_load}</strong> beban ≥ 90%
                                </span>
                            </div>
                        ) : null}
                        {stats.unlinked > 0 ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setAsetLink('unlinked');
                                    applyFilters({ aset_link: 'unlinked' });
                                }}
                                className="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-muted/40 px-3 py-2 text-left text-sm transition-colors hover:bg-muted/60"
                            >
                                <Unlink className="size-4 shrink-0 text-muted-foreground" />
                                <span>
                                    <strong>{stats.unlinked}</strong> belum terhubung inventaris
                                </span>
                            </button>
                        ) : null}
                    </div>
                )}

                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {(
                        [
                            { key: 'all', label: 'Total', value: stats.total },
                            { key: 'online', label: 'Online', value: stats.online, tone: 'online' },
                            { key: 'offline', label: 'Offline', value: stats.offline, tone: 'offline' },
                            { key: 'high', label: 'High load', value: stats.high_load, tone: 'high' },
                        ] as const
                    ).map((stat) => (
                        <button
                            key={stat.key}
                            type="button"
                            disabled={stat.key === 'high'}
                            onClick={() => {
                                if (stat.key === 'high') {
                                    return;
                                }
                                const next = stat.key === 'all' ? 'all' : stat.key;
                                setStatus(next);
                                applyFilters({ status: next });
                            }}
                            className={cn(
                                'rounded-xl border px-3 py-2.5 text-left transition-colors',
                                status === (stat.key === 'all' ? 'all' : stat.key)
                                    ? 'border-teal-700/30 bg-teal-50/80 dark:border-teal-500/30 dark:bg-teal-950/30'
                                    : 'border-border/80 bg-card hover:bg-muted/40',
                                stat.key === 'high' && 'cursor-default',
                                stat.key !== 'high' && 'cursor-pointer',
                            )}
                        >
                            <div className="text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                                {stat.label}
                            </div>
                            <div
                                className={cn(
                                    'mt-1 text-xl font-semibold tabular-nums',
                                    stat.tone === 'online' && 'text-emerald-700 dark:text-emerald-300',
                                    stat.tone === 'offline' && stats.offline > 0 && 'text-amber-700 dark:text-amber-300',
                                    stat.tone === 'high' && stats.high_load > 0 && 'text-red-700 dark:text-red-300',
                                )}
                            >
                                {stat.value}
                            </div>
                        </button>
                    ))}
                </div>

                <form
                    onSubmit={submit}
                    className="flex flex-col gap-2 rounded-xl border border-border/80 bg-card p-3 shadow-[0_1px_2px_rgba(0,0,0,0.03)] lg:flex-row lg:items-center"
                >
                    <div className="relative min-w-0 flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(event) => setQ(event.target.value)}
                            placeholder="Cari hostname, IP, UUID…"
                            className="h-10 pl-9"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:flex">
                        <Select
                            value={status}
                            onValueChange={(value) => {
                                setStatus(value);
                                applyFilters({ status: value });
                            }}
                        >
                            <SelectTrigger className="h-10 lg:w-[130px]">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua status</SelectItem>
                                <SelectItem value="online">Online</SelectItem>
                                <SelectItem value="offline">Offline</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={asetLink}
                            onValueChange={(value) => {
                                setAsetLink(value);
                                applyFilters({ aset_link: value });
                            }}
                        >
                            <SelectTrigger className="h-10 lg:w-[160px]">
                                <SelectValue placeholder="Aset" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua tautan</SelectItem>
                                <SelectItem value="linked">Terhubung aset</SelectItem>
                                <SelectItem value="unlinked">Belum terhubung</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={sort}
                            onValueChange={(value) => {
                                setSort(value);
                                applyFilters({ sort: value });
                            }}
                        >
                            <SelectTrigger className="col-span-2 h-10 sm:col-span-1 lg:w-[150px]">
                                <SelectValue placeholder="Urutkan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="last_seen">Last seen</SelectItem>
                                <SelectItem value="hostname">Hostname</SelectItem>
                                <SelectItem value="cpu">CPU tertinggi</SelectItem>
                                <SelectItem value="ram">RAM tertinggi</SelectItem>
                                <SelectItem value="disk">Disk tertinggi</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" size="sm" className="h-10 bg-teal-700 hover:bg-teal-800">
                            Cari
                        </Button>
                        {hasFilters ? (
                            <Button type="button" variant="ghost" size="icon" className="h-10 w-10" onClick={clearFilters} title="Reset filter">
                                <X className="size-4" />
                            </Button>
                        ) : null}
                    </div>
                </form>

                {devices.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/80 px-6 py-14 text-center">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                            <Activity className="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">
                                {hasFilters ? 'Tidak ada perangkat cocok filter' : 'Belum ada perangkat terdaftar'}
                            </p>
                            <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                {hasFilters
                                    ? 'Reset filter atau ubah kata kunci pencarian.'
                                    : 'Pasang RS Agent di PC, lalu daftar dengan enrollment key. Setelah online, hubungkan ke inventaris dari detail perangkat.'}
                            </p>
                        </div>
                        {hasFilters ? (
                            <Button type="button" variant="outline" size="sm" onClick={clearFilters}>
                                Reset filter
                            </Button>
                        ) : null}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[920px] text-left text-sm">
                                <thead className="border-b border-border/60 bg-muted/30 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-2.5">Perangkat</th>
                                        <th className="px-3 py-2.5">Status</th>
                                        <th className="px-3 py-2.5">Inventaris</th>
                                        <th className="px-3 py-2.5">CPU</th>
                                        <th className="px-3 py-2.5">RAM</th>
                                        <th className="px-3 py-2.5">Disk</th>
                                        <th className="px-4 py-2.5">Last seen</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/60">
                                    {devices.data.map((device) => (
                                        <tr key={device.id} className="transition-colors hover:bg-muted/35">
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/monitoring/${device.id}`}
                                                    prefetch
                                                    className="font-semibold tracking-tight text-foreground hover:text-teal-800 dark:hover:text-teal-300"
                                                >
                                                    {device.hostname || device.computer_name || device.uuid}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-muted-foreground">
                                                    {device.ip_address || '—'}
                                                    {device.hardware?.os ? ` · ${device.hardware.os}` : ''}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className={cn(
                                                        'capitalize',
                                                        device.status === 'online'
                                                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
                                                    )}
                                                >
                                                    {device.status}
                                                </Badge>
                                            </td>
                                            <td className="px-3 py-3">
                                                {device.aset ? (
                                                    <Link
                                                        href={`/aset/${device.aset.kode_aset}`}
                                                        className="inline-flex items-center gap-1 text-sm font-medium underline-offset-2 hover:underline"
                                                        onClick={(e) => e.stopPropagation()}
                                                    >
                                                        <Link2 className="size-3.5 text-teal-700 dark:text-teal-400" />
                                                        {device.aset.kode_aset}
                                                    </Link>
                                                ) : (
                                                    <Link
                                                        href={`/monitoring/${device.id}`}
                                                        className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                                    >
                                                        <Unlink className="size-3.5" />
                                                        Hubungkan
                                                    </Link>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                <MetricBar value={device.last_cpu_percent} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <MetricBar value={device.last_ram_percent} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <MetricBar value={device.last_disk_percent} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="text-sm">{formatRelativeId(device.last_seen_at)}</div>
                                                <div className="text-[11px] text-muted-foreground">
                                                    {device.last_seen_at
                                                        ? new Date(device.last_seen_at).toLocaleString('id-ID')
                                                        : '–'}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {devices.links.length > 3 ? (
                    <div className="flex flex-wrap gap-1">
                        {devices.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
