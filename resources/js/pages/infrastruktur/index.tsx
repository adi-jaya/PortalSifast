import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Download,
    RefreshCw,
    Server,
    Wifi,
    WifiOff,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatPercent, formatRelativeId, formatUptime } from '@/lib/monitoring';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type TianjiMonitor = {
    id: string;
    name: string;
    type: string;
    target: string;
    active: boolean;
    current_status: 'up' | 'down' | 'unknown';
    current_latency_ms: number | null;
    period_total_checks: number;
    period_up_checks: number;
    period_down_checks: number;
    period_uptime_percent: number | null;
};

type TianjiEvent = {
    id: string;
    monitor_id: string;
    type: string;
    message: string;
    created_at: string | null;
};

type AgentDevice = {
    id: number;
    hostname: string | null;
    computer_name: string | null;
    ip_address: string | null;
    status: 'online' | 'offline';
    last_seen_at: string | null;
    last_cpu_percent: string | number | null;
    last_ram_percent: string | number | null;
    last_disk_percent: string | number | null;
    uptime_seconds: number | null;
    avg_cpu_7d: number | null;
    avg_ram_7d: number | null;
    avg_disk_7d: number | null;
    aset: { id: number; kode_aset: string } | null;
    os: string | null;
};

type Props = {
    filters: { start_date: string; end_date: string };
    configured: boolean;
    tianji_error: string | null;
    summary: {
        tianji: {
            total: number;
            up_now: number;
            down_now: number;
            unknown_now: number;
            avg_uptime_percent: number | null;
        };
        agent: {
            total: number;
            online: number;
            offline: number;
            high_load: number;
        };
    };
    tianji: {
        monitors: TianjiMonitor[];
        events: TianjiEvent[];
    };
    agent: {
        devices: AgentDevice[];
    };
    export_urls: {
        ringkasan: string;
        harian: string;
        gangguan: string;
        agent: string;
        detail: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Kesehatan Infrastruktur', href: '/infrastruktur' },
];

function StatCard({
    label,
    value,
    hint,
    tone = 'default',
}: {
    label: string;
    value: string | number;
    hint?: string;
    tone?: 'default' | 'ok' | 'danger' | 'warn';
}) {
    return (
        <div
            className={cn(
                'rounded-lg border border-border/70 bg-card/40 px-4 py-3',
                tone === 'ok' && 'border-emerald-500/30',
                tone === 'danger' && 'border-destructive/30',
                tone === 'warn' && 'border-amber-500/30',
            )}
        >
            <p className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">{label}</p>
            <p
                className={cn(
                    'mt-1 text-2xl font-semibold tabular-nums tracking-tight',
                    tone === 'ok' && 'text-emerald-700 dark:text-emerald-400',
                    tone === 'danger' && 'text-destructive',
                    tone === 'warn' && 'text-amber-700 dark:text-amber-400',
                )}
            >
                {value}
            </p>
            {hint ? <p className="mt-1 text-xs text-muted-foreground">{hint}</p> : null}
        </div>
    );
}

function statusBadge(status: TianjiMonitor['current_status']) {
    if (status === 'up') {
        return <Badge className="bg-emerald-600 hover:bg-emerald-600">UP</Badge>;
    }
    if (status === 'down') {
        return <Badge variant="destructive">DOWN</Badge>;
    }

    return <Badge variant="secondary">?</Badge>;
}

export default function InfrastrukturIndex({
    filters,
    configured,
    tianji_error,
    summary,
    tianji,
    agent,
    export_urls,
}: Props) {
    const page = usePage<{ flash?: { error?: string } }>();
    const flashError = page.props.flash?.error ?? null;
    const displayError = flashError || tianji_error;

    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);
    const [exporting, setExporting] = useState<string | null>(null);

    function applyFilters(event: FormEvent) {
        event.preventDefault();
        router.get(
            '/infrastruktur',
            {
                start_date: startDate || undefined,
                end_date: endDate || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    function exportCsv(kind: keyof typeof export_urls, needsDate = true) {
        if (needsDate && (!startDate || !endDate)) {
            return;
        }

        setExporting(kind);
        const base = export_urls[kind];
        const qs = needsDate
            ? `?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`
            : '';
        window.location.assign(`${base}${qs}`);
        window.setTimeout(() => setExporting(null), 1500);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kesehatan Infrastruktur" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <p className="mb-1 text-[11px] font-medium tracking-[0.18em] text-teal-700 uppercase dark:text-teal-400">
                            Operasional IT
                        </p>
                        <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight text-foreground">
                            <Server className="size-6 shrink-0 text-teal-700 dark:text-teal-400" aria-hidden />
                            Kesehatan Infrastruktur
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Gabungan uptime layanan (Tianji) dan kesehatan perangkat (RS Agent) — tanpa matching host.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        className="cursor-pointer"
                        onClick={() =>
                            router.get('/infrastruktur', {
                                start_date: startDate || undefined,
                                end_date: endDate || undefined,
                            })
                        }
                    >
                        <RefreshCw className="size-4" aria-hidden />
                        Muat ulang
                    </Button>
                </header>

                {displayError ? (
                    <div
                        role="alert"
                        className="flex gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                    >
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden />
                        <div>
                            <p className="font-medium">Tianji bermasalah</p>
                            <p className="mt-0.5 opacity-90">{displayError}</p>
                            <p className="mt-1 text-xs text-muted-foreground">Blok RS Agent tetap ditampilkan di bawah.</p>
                        </div>
                    </div>
                ) : null}

                <form
                    onSubmit={applyFilters}
                    className="flex flex-col gap-4 rounded-lg border border-border/70 bg-card/40 p-4 sm:flex-row sm:flex-wrap sm:items-end"
                >
                    <div className="grid min-w-[10rem] flex-1 gap-1.5">
                        <Label htmlFor="start_date">Tanggal mulai</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            required
                            className="min-h-11"
                        />
                    </div>
                    <div className="grid min-w-[10rem] flex-1 gap-1.5">
                        <Label htmlFor="end_date">Tanggal selesai</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            required
                            className="min-h-11"
                        />
                        <p className="text-xs text-muted-foreground">Uptime Tianji dihitung dari ringkasan harian (max 31 hari).</p>
                    </div>
                    <Button type="submit" variant="secondary" className="min-h-11 cursor-pointer">
                        Terapkan filter
                    </Button>
                </form>

                <section aria-label="Ringkasan" className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Monitor Tianji" value={summary.tianji.total} hint="Total monitor" />
                    <StatCard
                        label="Tianji UP sekarang"
                        value={summary.tianji.up_now}
                        tone="ok"
                        hint={`${summary.tianji.down_now} down · ${summary.tianji.unknown_now} unknown`}
                    />
                    <StatCard
                        label="Uptime rata-rata periode"
                        value={
                            summary.tianji.avg_uptime_percent === null
                                ? '—'
                                : `${summary.tianji.avg_uptime_percent}%`
                        }
                        hint={`${filters.start_date} s/d ${filters.end_date}`}
                    />
                    <StatCard
                        label="RS Agent online"
                        value={`${summary.agent.online}/${summary.agent.total}`}
                        tone={summary.agent.offline > 0 ? 'warn' : 'ok'}
                        hint={`${summary.agent.high_load} high load · ${summary.agent.offline} offline`}
                    />
                </section>

                <section className="overflow-hidden rounded-lg border border-border/70">
                    <div className="flex flex-col gap-3 border-b border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="flex items-center gap-2 text-sm font-medium text-foreground">
                                <Wifi className="size-4 text-teal-700 dark:text-teal-400" aria-hidden />
                                Blok A — Tianji (uptime layanan)
                            </h2>
                            <p className="text-xs text-muted-foreground">Ping / HTTP / TCP dari instance Tianji lokal</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                className="cursor-pointer"
                                disabled={!configured || !!tianji_error || exporting !== null}
                                onClick={() => exportCsv('harian')}
                                aria-busy={exporting === 'harian'}
                            >
                                <Download className="size-3.5" aria-hidden />
                                {exporting === 'harian' ? 'Menyiapkan…' : 'CSV uptime harian'}
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="cursor-pointer"
                                disabled={!configured || !!tianji_error || exporting !== null}
                                onClick={() => exportCsv('gangguan')}
                                aria-busy={exporting === 'gangguan'}
                            >
                                <Download className="size-3.5" aria-hidden />
                                {exporting === 'gangguan' ? 'Menyiapkan…' : 'CSV gangguan'}
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="cursor-pointer"
                                disabled={!configured || !!tianji_error || exporting !== null}
                                onClick={() => exportCsv('ringkasan')}
                                aria-busy={exporting === 'ringkasan'}
                            >
                                <Download className="size-3.5" aria-hidden />
                                {exporting === 'ringkasan' ? 'Menyiapkan…' : 'CSV ringkasan'}
                            </Button>
                        </div>
                    </div>

                    {tianji.monitors.length === 0 ? (
                        <div className="px-4 py-10 text-center text-sm text-muted-foreground">
                            {configured
                                ? 'Tidak ada monitor Tianji, atau data tidak bisa dimuat.'
                                : 'Lengkapi konfigurasi Tianji di server Portal.'}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[48rem] text-left text-sm">
                                <thead className="bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Nama</th>
                                        <th className="px-4 py-3 font-medium">Target</th>
                                        <th className="px-4 py-3 font-medium">Sekarang</th>
                                        <th className="px-4 py-3 font-medium">Uptime periode</th>
                                        <th className="px-4 py-3 font-medium">Down checks</th>
                                        <th className="px-4 py-3 font-medium">Latency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tianji.monitors.map((monitor) => (
                                        <tr key={monitor.id} className="border-t border-border/60">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-foreground">{monitor.name}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {monitor.type}
                                                    {!monitor.active ? ' · nonaktif' : ''}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs">{monitor.target || '—'}</td>
                                            <td className="px-4 py-3">{statusBadge(monitor.current_status)}</td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {monitor.period_uptime_percent === null
                                                    ? '—'
                                                    : `${monitor.period_uptime_percent}%`}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums text-muted-foreground">
                                                {monitor.period_down_checks}/{monitor.period_total_checks}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums text-muted-foreground">
                                                {monitor.current_latency_ms !== null
                                                    ? `${monitor.current_latency_ms} ms`
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="border-t border-border/70 px-4 py-3">
                        <h3 className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Kejadian terbaru
                        </h3>
                        {tianji.events.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Belum ada event, atau Tianji tidak tersedia.</p>
                        ) : (
                            <ul className="space-y-2">
                                {tianji.events.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex gap-3 rounded-md border border-border/50 px-3 py-2 text-sm"
                                    >
                                        <Badge
                                            variant={event.type === 'DOWN' ? 'destructive' : 'secondary'}
                                            className="shrink-0"
                                        >
                                            {event.type || 'EVENT'}
                                        </Badge>
                                        <div className="min-w-0">
                                            <p className="truncate text-foreground">{event.message}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {event.created_at ? formatRelativeId(event.created_at) : '—'}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section className="overflow-hidden rounded-lg border border-border/70">
                    <div className="flex flex-col gap-2 border-b border-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="flex items-center gap-2 text-sm font-medium text-foreground">
                                <Activity className="size-4 text-teal-700 dark:text-teal-400" aria-hidden />
                                Blok B — RS Agent (kesehatan perangkat)
                            </h2>
                            <p className="text-xs text-muted-foreground">CPU / RAM / Disk dari agent di PortalSifast</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                className="cursor-pointer"
                                disabled={exporting !== null || agent.devices.length === 0}
                                onClick={() => exportCsv('agent', false)}
                                aria-busy={exporting === 'agent'}
                            >
                                <Download className="size-3.5" aria-hidden />
                                {exporting === 'agent' ? 'Menyiapkan…' : 'CSV perangkat'}
                            </Button>
                            <Button asChild size="sm" variant="outline" className="cursor-pointer">
                                <Link href="/monitoring">
                                    Buka Monitoring
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {agent.devices.length === 0 ? (
                        <div className="px-4 py-10 text-center text-sm text-muted-foreground">
                            Belum ada perangkat RS Agent terdaftar.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[52rem] text-left text-sm">
                                <thead className="bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Perangkat</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">CPU</th>
                                        <th className="px-4 py-3 font-medium">RAM</th>
                                        <th className="px-4 py-3 font-medium">Disk</th>
                                        <th className="px-4 py-3 font-medium">Avg 7d</th>
                                        <th className="px-4 py-3 font-medium">Last seen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {agent.devices.map((device) => (
                                        <tr key={device.id} className="border-t border-border/60">
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/monitoring/${device.id}`}
                                                    className="font-medium text-foreground underline-offset-2 hover:underline"
                                                >
                                                    {device.computer_name || device.hostname || `Device #${device.id}`}
                                                </Link>
                                                <div className="font-mono text-xs text-muted-foreground">
                                                    {device.ip_address || '—'}
                                                    {device.aset ? ` · ${device.aset.kode_aset}` : ''}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {device.status === 'online' ? (
                                                    <Badge className="gap-1 bg-emerald-600 hover:bg-emerald-600">
                                                        <Wifi className="size-3" aria-hidden />
                                                        Online
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="secondary" className="gap-1">
                                                        <WifiOff className="size-3" aria-hidden />
                                                        Offline
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">{formatPercent(device.last_cpu_percent)}</td>
                                            <td className="px-4 py-3 tabular-nums">{formatPercent(device.last_ram_percent)}</td>
                                            <td className="px-4 py-3 tabular-nums">{formatPercent(device.last_disk_percent)}</td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground tabular-nums">
                                                {device.avg_cpu_7d === null
                                                    ? '—'
                                                    : `C ${device.avg_cpu_7d}% · R ${device.avg_ram_7d}%`}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-muted-foreground">
                                                <div>{formatRelativeId(device.last_seen_at)}</div>
                                                <div>Uptime {formatUptime(device.uptime_seconds)}</div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
