import { Head, Link, router, usePoll } from '@inertiajs/react';
import { Activity, AlertTriangle, RefreshCw, Search, X } from 'lucide-react';
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
    };
    stats: {
        total: number;
        online: number;
        offline: number;
        high_load: number;
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

    usePoll(30000, {
        only: ['devices', 'stats'],
    });

    function applyFilters(next?: { status?: string; sort?: string }) {
        const nextStatus = next?.status ?? status;
        const nextSort = next?.sort ?? sort;

        router.get(
            '/monitoring',
            {
                q: q || undefined,
                status: nextStatus === 'all' ? undefined : nextStatus,
                sort: nextSort === 'last_seen' ? undefined : nextSort,
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
        router.get('/monitoring', {}, { preserveState: true });
    }

    const hasFilters = Boolean(filters.q || filters.status || filters.sort);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monitoring" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-semibold tracking-tight">
                            <Activity className="size-5 text-muted-foreground" />
                            Monitoring Perangkat
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Status online/offline dan metrik CPU, RAM, Disk dari RS Agent. Auto-refresh 30 detik.
                        </p>
                    </div>
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

                {(stats.offline > 0 || stats.high_load > 0) && (
                    <div className="flex flex-wrap gap-2">
                        {stats.offline > 0 ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setStatus('offline');
                                    applyFilters({ status: 'offline' });
                                }}
                                className="flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-left text-sm text-amber-900 dark:text-amber-200"
                            >
                                <AlertTriangle className="size-4 shrink-0" />
                                <span>
                                    <strong>{stats.offline}</strong> perangkat offline — klik untuk filter
                                </span>
                            </button>
                        ) : null}
                        {stats.high_load > 0 ? (
                            <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-800 dark:text-red-200">
                                <AlertTriangle className="size-4 shrink-0" />
                                <span>
                                    <strong>{stats.high_load}</strong> online dengan beban ≥ 90% (CPU/RAM/Disk)
                                </span>
                            </div>
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
                                'rounded-lg border px-3 py-2.5 text-left transition-colors',
                                status === (stat.key === 'all' ? 'all' : stat.key)
                                    ? 'border-foreground/20 bg-muted/60'
                                    : 'hover:bg-muted/40',
                                stat.key === 'high' && 'cursor-default',
                            )}
                        >
                            <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
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

                <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
                    <div className="relative min-w-[220px] flex-1">
                        <Search className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
                        <Input
                            value={q}
                            onChange={(event) => setQ(event.target.value)}
                            placeholder="Cari hostname, IP, UUID…"
                            className="pl-8"
                        />
                    </div>
                    <Select
                        value={status}
                        onValueChange={(value) => {
                            setStatus(value);
                            applyFilters({ status: value });
                        }}
                    >
                        <SelectTrigger className="w-[140px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua</SelectItem>
                            <SelectItem value="online">Online</SelectItem>
                            <SelectItem value="offline">Offline</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={sort}
                        onValueChange={(value) => {
                            setSort(value);
                            applyFilters({ sort: value });
                        }}
                    >
                        <SelectTrigger className="w-[160px]">
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
                    <Button type="submit" variant="secondary">
                        Cari
                    </Button>
                    {hasFilters ? (
                        <Button type="button" variant="ghost" size="icon" onClick={clearFilters} title="Reset filter">
                            <X className="size-4" />
                        </Button>
                    ) : null}
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[860px] text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="px-3 py-2 font-medium">Perangkat</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                                <th className="px-3 py-2 font-medium">CPU</th>
                                <th className="px-3 py-2 font-medium">RAM</th>
                                <th className="px-3 py-2 font-medium">Disk</th>
                                <th className="px-3 py-2 font-medium">Last seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            {devices.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-3 py-10 text-center text-muted-foreground">
                                        Belum ada perangkat terdaftar.
                                    </td>
                                </tr>
                            ) : (
                                devices.data.map((device) => (
                                    <tr key={device.id} className="border-b last:border-0 hover:bg-muted/30">
                                        <td className="px-3 py-2.5">
                                            <Link
                                                href={`/monitoring/${device.id}`}
                                                prefetch
                                                className="font-medium text-foreground hover:underline"
                                            >
                                                {device.hostname || device.computer_name || device.uuid}
                                            </Link>
                                            <div className="mt-0.5 text-xs text-muted-foreground">
                                                {device.ip_address || '—'}
                                                {device.hardware?.os ? ` · ${device.hardware.os}` : ''}
                                                {device.aset ? (
                                                    <>
                                                        {' · '}
                                                        <Link
                                                            href={`/aset/${device.aset.kode_aset}`}
                                                            className="text-foreground/80 underline-offset-2 hover:underline"
                                                            onClick={(e) => e.stopPropagation()}
                                                        >
                                                            {device.aset.kode_aset}
                                                        </Link>
                                                    </>
                                                ) : null}
                                            </div>
                                        </td>
                                        <td className="px-3 py-2.5">
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
                                        <td className="px-3 py-2.5">
                                            <MetricBar value={device.last_cpu_percent} />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <MetricBar value={device.last_ram_percent} />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <MetricBar value={device.last_disk_percent} />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <div className="text-sm">{formatRelativeId(device.last_seen_at)}</div>
                                            <div className="text-[11px] text-muted-foreground">
                                                {device.last_seen_at
                                                    ? new Date(device.last_seen_at).toLocaleString('id-ID')
                                                    : '–'}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

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
