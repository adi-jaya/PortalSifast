import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, Trash2, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { AssetLinkPicker } from '@/components/monitoring/asset-link-picker';
import { MetricTrendChart } from '@/components/monitoring/metric-trend-chart';
import { CommandBar } from '@/components/monitoring/ops-console/CommandBar';
import { DesktopPreview } from '@/components/monitoring/ops-console/DesktopPreview';
import { MetricsSidebar } from '@/components/monitoring/ops-console/MetricsSidebar';
import type {
    DesktopSnapshotMeta,
    LastCaptureCommand,
    LastKillCommand,
    LastListCommand,
    PendingCaptureCommand,
    PendingRemoteCommand,
    ProcessRow,
    SuspiciousProcess,
    WindowApp,
} from '@/components/monitoring/ops-console/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { formatRelativeId, formatUptime } from '@/lib/monitoring';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Hardware = {
    os: string | null;
    os_version: string | null;
    architecture: string | null;
    cpu_model: string | null;
    cpu_cores: number | null;
    ram_total_mb: number | null;
    disk_total_gb: number | null;
    manufacturer: string | null;
    model: string | null;
    serial_number: string | null;
    motherboard: string | null;
    bios: string | null;
    timezone: string | null;
    domain: string | null;
    username: string | null;
};

type Sample = {
    id: number;
    cpu_percent: string | number | null;
    ram_percent: string | number | null;
    disk_percent: string | number | null;
    uptime_seconds: number | null;
    collected_at: string;
};

type Device = {
    id: number;
    uuid: string;
    hostname: string | null;
    computer_name: string | null;
    ip_address: string | null;
    mac_address: string | null;
    agent_version: string | null;
    status: 'online' | 'offline';
    last_seen_at: string | null;
    last_cpu_percent: string | number | null;
    last_ram_percent: string | number | null;
    last_disk_percent: string | number | null;
    uptime_seconds: number | null;
    critical_software: CriticalSoftware[] | null;
    usb_inventory: UsbInventory | null;
    sensors: Sensors | null;
    hardware: Hardware | null;
    aset: { id: number; kode_aset: string; no_seri: string | null } | null;
};

type CriticalSoftware = {
    id: string;
    name: string;
    status: 'running' | 'installed' | 'missing';
    detail?: string | null;
};

type UsbDevice = {
    name: string;
    kind: 'storage' | 'printer' | 'hub' | 'hid' | 'other';
    device_id?: string | null;
};

type UsbInventory = {
    ports_total: number;
    ports_used: number;
    ports_empty: number;
    removable_storage_count: number;
    has_removable_storage: boolean;
    printer_count: number;
    estimated: boolean;
    note?: string | null;
    devices: UsbDevice[];
};

type SensorReading = {
    name: string;
    temperature_c: number;
};

type Sensors = {
    supported: boolean;
    note?: string | null;
    readings: SensorReading[];
};

type LinkableAsset = {
    id: number;
    label: string;
};

type ActiveDuplicateDevice = {
    id: number;
    hostname: string | null;
    last_seen_at?: string | null;
};

type Props = {
    device: Device;
    recentSamples: Sample[];
    linkableAssets: LinkableAsset[];
    canRemoteApps?: boolean;
    windowSnapshot?: { windows: WindowApp[] } | null;
    windowSnapshotAt?: string | null;
    processSnapshot?: { processes: ProcessRow[] } | null;
    processSnapshotAt?: string | null;
    suspiciousProcesses?: SuspiciousProcess[];
    suspiciousProcessesAt?: string | null;
    desktopSnapshotUrl?: string | null;
    desktopSnapshotAt?: string | null;
    desktopSnapshotMeta?: DesktopSnapshotMeta | null;
    pendingRemoteCommand?: PendingRemoteCommand | null;
    pendingCaptureCommand?: PendingCaptureCommand | null;
    lastListCommand?: LastListCommand | null;
    lastKillCommand?: LastKillCommand | null;
    lastCaptureCommand?: LastCaptureCommand | null;
    activeDuplicateDevice?: ActiveDuplicateDevice | null;
    agentDefaultInterval?: number;
};

function dash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

function temperatureBadgeClass(celsius: number): string {
    if (celsius >= 80) {
        return 'border-rose-500/40 bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }
    if (celsius >= 60) {
        return 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-200';
    }
    return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
}

function Field({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 text-sm">{value}</dd>
        </div>
    );
}

function Panel({ title, hint, children }: { title: string; hint?: string; children: ReactNode }) {
    return (
        <section className="overflow-hidden rounded-xl border border-border/80 bg-card shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <div className="border-b border-border/60 bg-muted/30 px-4 py-3">
                <h2 className="text-sm font-semibold tracking-tight">{title}</h2>
                {hint ? <p className="mt-0.5 text-xs text-muted-foreground">{hint}</p> : null}
            </div>
            <div className="p-4">{children}</div>
        </section>
    );
}

function agentAtLeast(version: string | null, min: number): boolean {
    if (!version) {
        return false;
    }

    const normalized = parseFloat(version.replace(/-.*$/, ''));
    return !Number.isNaN(normalized) && normalized >= min;
}

function reasonLabel(reason: string): string {
    switch (reason) {
        case 'known_miner_name':
            return 'Nama miner dikenal';
        case 'suspicious_path':
            return 'Path mencurigakan';
        case 'high_cpu':
            return 'CPU tinggi';
        default:
            return reason;
    }
}

function RemoteAppsPanel({
    deviceId,
    deviceOnline,
    snapshot,
    snapshotAt,
    pending,
    lastListCommand,
    lastKillCommand,
}: {
    deviceId: number;
    deviceOnline: boolean;
    snapshot: { windows: WindowApp[] } | null;
    snapshotAt: string | null;
    pending: PendingRemoteCommand | null;
    lastListCommand: LastListCommand | null;
    lastKillCommand: LastKillCommand | null;
}) {
    const [busy, setBusy] = useState(false);
    const windows = snapshot?.windows ?? [];
    const killWaiting = pending !== null && pending.type === 'kill_pid';
    const listWaiting = pending !== null && pending.type === 'list_windows';
    const lastFailed =
        lastListCommand?.type === 'list_windows' &&
        lastListCommand.status === 'failed' &&
        lastListCommand.error;
    const killFailed = lastKillCommand?.status === 'failed' && lastKillCommand.error;
    const killSucceeded =
        lastKillCommand?.status === 'succeeded' &&
        lastKillCommand.finished_at &&
        Date.now() - new Date(lastKillCommand.finished_at).getTime() < 120_000;

    const postCommand = (data: Record<string, string | number>) => {
        setBusy(true);
        router.post(`/monitoring/${deviceId}/commands`, data, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    };

    const refreshWindows = () => {
        postCommand({ type: 'list_windows' });
    };

    const closeApp = (row: WindowApp) => {
        if (
            !confirm(
                `Tutup "${row.title || row.exe}" (PID ${row.pid}) di PC ini?\n\nProses akan dihentikan tanpa konfirmasi di komputer user.`,
            )
        ) {
            return;
        }

        postCommand({ type: 'kill_pid', pid: row.pid, exe: row.exe });
    };

    return (
        <Panel
            title="Aplikasi terbuka"
            hint="Hanya jendela GUI berjudul. Tutup menunggu heartbeat agent (~10 detik). Jika agent offline, perintah antre."
        >
            {!deviceOnline && (killWaiting || listWaiting) ? (
                <p className="mb-3 rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                    Agent offline — perintah antre sampai PC online lagi. Pastikan service PortalSifast Agent jalan di PC target.
                </p>
            ) : null}
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-xs text-muted-foreground">
                        {killWaiting
                            ? `Menunggu agent menutup aplikasi (${pending?.status})…`
                            : listWaiting
                              ? `Menunggu agent memperbarui daftar (${pending?.status})…`
                              : snapshotAt
                                ? `Terakhir diambil ${formatRelativeId(snapshotAt)}`
                                : 'Belum ada daftar. Klik Refresh aplikasi di command bar.'}
                    </p>
                    {killFailed ? (
                        <p className="mt-1 text-xs text-rose-600 dark:text-rose-400">
                            Gagal tutup {lastKillCommand?.exe || 'proses'} (PID {lastKillCommand?.pid}): {lastKillCommand?.error}
                        </p>
                    ) : null}
                    {killSucceeded && !killWaiting ? (
                        <p className="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                            Berhasil tutup {lastKillCommand?.exe} (PID {lastKillCommand?.pid}).
                        </p>
                    ) : null}
                    {lastFailed ? (
                        <p className="mt-1 text-xs text-rose-600 dark:text-rose-400">Gagal refresh jendela: {lastFailed}</p>
                    ) : null}
                </div>
                <Button type="button" variant="outline" size="sm" disabled={busy || killWaiting} onClick={refreshWindows}>
                    <RefreshCw className={cn('size-3.5', listWaiting || busy ? 'animate-spin' : '')} />
                    Refresh aplikasi
                </Button>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[480px] text-left text-sm">
                    <thead className="border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th className="py-2 pr-3 font-medium">Jendela</th>
                            <th className="py-2 pr-3 font-medium">Exe</th>
                            <th className="py-2 pr-3 font-medium">PID</th>
                            <th className="py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {windows.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="py-4 text-muted-foreground">
                                    Tidak ada jendela tercatat.
                                </td>
                            </tr>
                        ) : (
                            windows.map((row) => (
                                <tr key={`${row.pid}-${row.title}`} className="border-b last:border-0">
                                    <td className="py-2 pr-3">{row.title || '–'}</td>
                                    <td className="py-2 pr-3 font-mono text-xs">{row.exe || '–'}</td>
                                    <td className="py-2 pr-3 font-mono text-xs">{row.pid}</td>
                                    <td className="py-2">
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            disabled={busy}
                                            onClick={() => closeApp(row)}
                                        >
                                            <X className="size-3.5" />
                                            Tutup
                                        </Button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}

function SuspiciousProcessesPanel({
    items,
    collectedAt,
}: {
    items: SuspiciousProcess[];
    collectedAt: string | null;
}) {
    return (
        <Panel
            title="Proses mencurigakan"
            hint="Deteksi otomatis tiap heartbeat (~10 detik). Mining/background sering muncul di sini, bukan di jendela GUI."
        >
            <p className="mb-3 text-xs text-muted-foreground">
                {collectedAt
                    ? `Terakhir dianalisis ${formatRelativeId(collectedAt)}`
                    : 'Menunggu data dari agent ≥ 0.4.0.'}
            </p>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Tidak ada proses mencurigakan terdeteksi.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[520px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="py-2 pr-3 font-medium">Exe</th>
                                <th className="py-2 pr-3 font-medium">PID</th>
                                <th className="py-2 pr-3 font-medium">CPU</th>
                                <th className="py-2 pr-3 font-medium">Path</th>
                                <th className="py-2 font-medium">Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((row) => (
                                <tr key={`${row.pid}-${row.exe}`} className="border-b last:border-0">
                                    <td className="py-2 pr-3 font-mono text-xs">{row.exe}</td>
                                    <td className="py-2 pr-3 font-mono text-xs">{row.pid}</td>
                                    <td className="py-2 pr-3">{row.cpu_percent != null ? `${row.cpu_percent}%` : '–'}</td>
                                    <td className="max-w-[200px] truncate py-2 pr-3 text-xs text-muted-foreground" title={row.path ?? ''}>
                                        {row.path || '–'}
                                    </td>
                                    <td className="py-2">
                                        <div className="flex flex-wrap gap-1">
                                            {row.reasons.map((reason) => (
                                                <Badge
                                                    key={reason}
                                                    variant="outline"
                                                    className="border-rose-500/40 bg-rose-500/10 text-rose-700 dark:text-rose-300"
                                                >
                                                    {reasonLabel(reason)}
                                                </Badge>
                                            ))}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Panel>
    );
}

function ProcessListPanel({
    deviceId,
    snapshot,
    snapshotAt,
    pending,
    lastListCommand,
}: {
    deviceId: number;
    snapshot: { processes: ProcessRow[] } | null;
    snapshotAt: string | null;
    pending: PendingRemoteCommand | null;
    lastListCommand: LastListCommand | null;
}) {
    const [busy, setBusy] = useState(false);
    const [filter, setFilter] = useState('');
    const processes = snapshot?.processes ?? [];
    const waiting = pending !== null && pending.type === 'list_processes';
    const lastFailed =
        lastListCommand?.type === 'list_processes' &&
        lastListCommand.status === 'failed' &&
        lastListCommand.error;

    const postCommand = (data: Record<string, string | number>) => {
        setBusy(true);
        router.post(`/monitoring/${deviceId}/commands`, data, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    };

    const refreshProcesses = () => {
        postCommand({ type: 'list_processes' });
    };

    const killProcess = (row: ProcessRow) => {
        if (
            !confirm(
                `Hentikan proses "${row.exe}" (PID ${row.pid}) di PC ini?\n\nProses akan dihentikan tanpa konfirmasi di komputer user.`,
            )
        ) {
            return;
        }

        postCommand({ type: 'kill_pid', pid: row.pid, exe: row.exe });
    };

    const needle = filter.trim().toLowerCase();
    const filtered = needle
        ? processes.filter(
              (row) =>
                  row.exe.toLowerCase().includes(needle) ||
                  (row.path ?? '').toLowerCase().includes(needle) ||
                  (row.user ?? '').toLowerCase().includes(needle),
          )
        : processes;

    return (
        <Panel
            title="Semua proses"
            hint="Seperti Task Manager — termasuk background/mining. Refresh on-demand (~10 detik). Agent ≥ 0.4.0."
        >
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-xs text-muted-foreground">
                        {waiting
                            ? `Menunggu agent (daftar proses · ${pending?.status})`
                            : snapshotAt
                              ? `Terakhir diambil ${formatRelativeId(snapshotAt)} · ${processes.length} proses`
                              : 'Belum ada daftar. Klik Semua proses di command bar.'}
                    </p>
                    {lastFailed ? (
                        <p className="mt-1 text-xs text-rose-600 dark:text-rose-400">Gagal: {lastFailed}</p>
                    ) : null}
                </div>
                <Button type="button" variant="outline" size="sm" disabled={busy} onClick={refreshProcesses}>
                    <RefreshCw className={cn('size-3.5', waiting || busy ? 'animate-spin' : '')} />
                    Refresh proses
                </Button>
            </div>
            {processes.length > 0 ? (
                <input
                    type="search"
                    placeholder="Filter exe, path, user…"
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    className="mb-3 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                />
            ) : null}
            <div className="overflow-x-auto">
                <table className="w-full min-w-[640px] text-left text-sm">
                    <thead className="border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th className="py-2 pr-3 font-medium">Exe</th>
                            <th className="py-2 pr-3 font-medium">PID</th>
                            <th className="py-2 pr-3 font-medium">CPU</th>
                            <th className="py-2 pr-3 font-medium">User</th>
                            <th className="py-2 pr-3 font-medium">Path</th>
                            <th className="py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filtered.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="py-4 text-muted-foreground">
                                    {processes.length === 0 ? 'Tidak ada proses tercatat.' : 'Tidak ada hasil filter.'}
                                </td>
                            </tr>
                        ) : (
                            filtered.map((row) => (
                                <tr key={`${row.pid}-${row.exe}`} className="border-b last:border-0">
                                    <td className="py-2 pr-3 font-mono text-xs">{row.exe}</td>
                                    <td className="py-2 pr-3 font-mono text-xs">{row.pid}</td>
                                    <td className="py-2 pr-3">{row.cpu_percent != null ? `${row.cpu_percent}%` : '–'}</td>
                                    <td className="py-2 pr-3 text-xs">{row.user || '–'}</td>
                                    <td className="max-w-[220px] truncate py-2 pr-3 text-xs text-muted-foreground" title={row.path ?? ''}>
                                        {row.path || '–'}
                                    </td>
                                    <td className="py-2">
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="sm"
                                            disabled={busy}
                                            onClick={() => killProcess(row)}
                                        >
                                            <X className="size-3.5" />
                                            Hentikan
                                        </Button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}

export default function MonitoringShow({
    device,
    recentSamples,
    linkableAssets,
    canRemoteApps = false,
    windowSnapshot = null,
    windowSnapshotAt = null,
    processSnapshot = null,
    processSnapshotAt = null,
    suspiciousProcesses = [],
    suspiciousProcessesAt = null,
    desktopSnapshotUrl = null,
    desktopSnapshotAt = null,
    desktopSnapshotMeta = null,
    pendingRemoteCommand = null,
    pendingCaptureCommand = null,
    lastListCommand = null,
    lastKillCommand = null,
    lastCaptureCommand = null,
    activeDuplicateDevice = null,
    agentDefaultInterval = 10,
}: Props) {
    const title = device.hostname || device.computer_name || device.uuid;
    const deviceOnline = device.status === 'online';
    const canCapture = agentAtLeast(device.agent_version, 0.5);
    const liveIntervalSec = agentDefaultInterval;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Monitoring', href: '/monitoring' },
        { title, href: `/monitoring/${device.id}` },
    ];

    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm<{
        aset_id: number | null;
    }>({
        aset_id: device.aset?.id ?? null,
    });

    useEffect(() => {
        setData('aset_id', device.aset?.id ?? null);
    }, [device.aset?.id, setData]);

    const remoteOnly = useMemo(
        () => [
            'device',
            'recentSamples',
            'linkableAssets',
            'windowSnapshot',
            'windowSnapshotAt',
            'processSnapshot',
            'processSnapshotAt',
            'suspiciousProcesses',
            'suspiciousProcessesAt',
            'desktopSnapshotUrl',
            'desktopSnapshotAt',
            'desktopSnapshotMeta',
            'pendingRemoteCommand',
            'pendingCaptureCommand',
            'lastListCommand',
            'lastKillCommand',
            'lastCaptureCommand',
            'activeDuplicateDevice',
            'canRemoteApps',
            'agentDefaultInterval',
        ],
        [],
    );

    const pollInterval =
        pendingRemoteCommand !== null
            ? 5000
            : canRemoteApps && deviceOnline
              ? 10000
              : 30000;

    usePoll(pollInterval, {
        only: remoteOnly,
    });

    const [commandBusy, setCommandBusy] = useState(false);

    const postCommand = useCallback(
        (payload: Record<string, string | number>) => {
            setCommandBusy(true);
            router.post(`/monitoring/${device.id}/commands`, payload, {
                preserveScroll: true,
                onFinish: () => setCommandBusy(false),
            });
        },
        [device.id],
    );

    useEffect(() => {
        if (!canRemoteApps || !deviceOnline || !canCapture) {
            return;
        }

        if (pendingCaptureCommand !== null) {
            return;
        }

        const ms = Math.max(liveIntervalSec, 10) * 1000;

        const timer = window.setInterval(() => {
            router.post(
                `/monitoring/${device.id}/commands`,
                { type: 'capture_desktop' },
                {
                    preserveScroll: true,
                    preserveState: true,
                },
            );
        }, ms);

        return () => window.clearInterval(timer);
    }, [
        canRemoteApps,
        deviceOnline,
        canCapture,
        device.id,
        pendingCaptureCommand,
        liveIntervalSec,
    ]);

    const submitAset = () => {
        patch(`/monitoring/${device.id}/aset`, { preserveScroll: true });
    };

    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        if (
            !confirm(
                `Hapus perangkat "${title}" dari monitoring?\n\nData hardware dan sampel metrik ikut terhapus. Jika agent masih aktif, perangkat bisa muncul lagi saat heartbeat berikutnya.`,
            )
        ) {
            return;
        }

        setDeleting(true);
        router.delete(`/monitoring/${device.id}`, {
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Monitoring · ${title}`} />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-4 md:p-6">
                {activeDuplicateDevice ? (
                    <div className="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-950 dark:text-amber-100">
                        PC ini terdeteksi <strong>online</strong> sebagai perangkat lain (
                        {activeDuplicateDevice.hostname || `#${activeDuplicateDevice.id}`}). Biasanya terjadi setelah
                        install ulang agent.{' '}
                        <Link href={`/monitoring/${activeDuplicateDevice.id}`} className="font-medium underline">
                            Buka perangkat aktif
                        </Link>{' '}
                        untuk perintah Tutup / Refresh, atau jalankan merge di server.
                    </div>
                ) : null}

                <header className="flex flex-wrap items-start justify-between gap-4 border-b border-border/70 pb-5">
                    <div className="min-w-0">
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                            <Link href="/monitoring">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                        <p className="text-[11px] font-medium tracking-[0.16em] text-teal-700 uppercase dark:text-teal-400">
                            Ops console
                        </p>
                        <h1 className="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">{title}</h1>
                        <p className="mt-1 font-mono text-xs text-muted-foreground">{device.uuid}</p>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <Badge
                                variant="outline"
                                className={cn(
                                    'capitalize',
                                    deviceOnline
                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                        : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-200',
                                )}
                            >
                                {device.status}
                            </Badge>
                            {device.agent_version ? (
                                <Badge variant="secondary">Agent {device.agent_version}</Badge>
                            ) : null}
                            {canRemoteApps && deviceOnline && canCapture ? (
                                <Badge
                                    variant="outline"
                                    className="border-teal-500/40 bg-teal-500/10 text-teal-700 dark:text-teal-300"
                                >
                                    Live {liveIntervalSec}s
                                </Badge>
                            ) : null}
                            {canRemoteApps && device.agent_version && !agentAtLeast(device.agent_version, 0.4) ? (
                                <Badge variant="outline" className="border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-200">
                                    Upgrade agent ≥ 0.4.0 untuk daftar proses
                                </Badge>
                            ) : null}
                            {canRemoteApps && device.agent_version && !canCapture ? (
                                <Badge variant="outline" className="border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-200">
                                    Upgrade agent ≥ 0.5.0 untuk live desktop
                                </Badge>
                            ) : null}
                            <span className="text-xs text-muted-foreground">
                                Last seen {formatRelativeId(device.last_seen_at)} · Uptime {formatUptime(device.uptime_seconds)}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload({ only: remoteOnly })}
                        >
                            <RefreshCw className="size-3.5" />
                            Refresh
                        </Button>
                        <Button type="button" variant="destructive" size="sm" disabled={deleting} onClick={handleDelete}>
                            <Trash2 className="size-3.5" />
                            Hapus
                        </Button>
                    </div>
                </header>

                {canRemoteApps ? (
                    <CommandBar
                        deviceOnline={deviceOnline}
                        canCapture={canCapture}
                        canRemoteApps={canRemoteApps}
                        pendingRemote={pendingRemoteCommand}
                        pendingCapture={pendingCaptureCommand}
                        onPostCommand={postCommand}
                        busy={commandBusy}
                    />
                ) : null}

                <div className={cn('grid gap-5', canRemoteApps ? 'lg:grid-cols-[1fr_280px]' : 'lg:grid-cols-1')}>
                    {canRemoteApps ? (
                        <DesktopPreview
                            snapshotUrl={desktopSnapshotUrl}
                            snapshotAt={desktopSnapshotAt}
                            snapshotMeta={desktopSnapshotMeta}
                            deviceOnline={deviceOnline}
                            pendingCapture={pendingCaptureCommand}
                            lastCaptureCommand={lastCaptureCommand}
                            canCapture={canCapture}
                            liveIntervalSec={liveIntervalSec}
                        />
                    ) : null}
                    <MetricsSidebar device={device} />
                </div>

                <Tabs defaultValue={canRemoteApps ? 'aplikasi' : 'hardware'} className="gap-4">
                    <TabsList variant="line" className="w-full justify-start border-b border-border/60 pb-0">
                        {canRemoteApps ? (
                            <>
                                <TabsTrigger value="aplikasi">Aplikasi</TabsTrigger>
                                <TabsTrigger value="proses">Proses</TabsTrigger>
                                <TabsTrigger value="keamanan">Keamanan</TabsTrigger>
                            </>
                        ) : null}
                        <TabsTrigger value="hardware">Hardware</TabsTrigger>
                    </TabsList>

                    {canRemoteApps ? (
                        <>
                            <TabsContent value="aplikasi">
                                <RemoteAppsPanel
                                    deviceId={device.id}
                                    deviceOnline={deviceOnline}
                                    snapshot={windowSnapshot}
                                    snapshotAt={windowSnapshotAt}
                                    pending={pendingRemoteCommand}
                                    lastListCommand={lastListCommand}
                                    lastKillCommand={lastKillCommand}
                                />
                            </TabsContent>
                            <TabsContent value="proses">
                                <ProcessListPanel
                                    deviceId={device.id}
                                    snapshot={processSnapshot}
                                    snapshotAt={processSnapshotAt}
                                    pending={pendingRemoteCommand}
                                    lastListCommand={lastListCommand}
                                />
                            </TabsContent>
                            <TabsContent value="keamanan">
                                <SuspiciousProcessesPanel items={suspiciousProcesses} collectedAt={suspiciousProcessesAt} />
                            </TabsContent>
                        </>
                    ) : null}

                    <TabsContent value="hardware" className="flex flex-col gap-5">
                        <AssetLinkPicker
                            linkedAset={device.aset}
                            options={linkableAssets}
                            value={data.aset_id}
                            onChange={(asetId) => setData('aset_id', asetId)}
                            onSubmit={submitAset}
                            processing={processing}
                            error={errors.aset_id}
                            recentlySuccessful={recentlySuccessful}
                        />

                        <Panel
                            title="Tren heartbeat"
                            hint={`${recentSamples.length} sample terakhir · kiri lebih lama · nilai atas = terbaru`}
                        >
                            <MetricTrendChart samples={recentSamples} />
                        </Panel>

                        <Panel title="Software kritis" hint="Tools remote / sync untuk dukungan jarak jauh.">
                            <div className="grid gap-2 sm:grid-cols-3">
                                {(device.critical_software ?? []).length === 0 ? (
                                    <p className="text-sm text-muted-foreground sm:col-span-3">
                                        Belum ada data (butuh agent terbaru).
                                    </p>
                                ) : (
                                    (device.critical_software ?? []).map((item) => (
                                        <div key={item.id} className="rounded-lg border border-border/70 px-3 py-2">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-sm font-medium">{item.name}</span>
                                                <Badge
                                                    variant="outline"
                                                    className={cn(
                                                        'capitalize',
                                                        item.status === 'running'
                                                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : item.status === 'installed'
                                                              ? 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-200'
                                                              : 'border-rose-500/40 bg-rose-500/10 text-rose-700 dark:text-rose-300',
                                                    )}
                                                >
                                                    {item.status}
                                                </Badge>
                                            </div>
                                            {item.detail ? (
                                                <p className="mt-1 truncate text-xs text-muted-foreground">{item.detail}</p>
                                            ) : null}
                                        </div>
                                    ))
                                )}
                            </div>
                        </Panel>

                        <Panel title="USB" hint="Port kosong/terpakai adalah estimasi. Flashdisk ditandai terpisah dari printer.">
                            {device.usb_inventory ? (
                                <>
                                    <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <Field label="Port total (estimasi)" value={dash(device.usb_inventory.ports_total)} />
                                        <Field label="Port terpakai" value={dash(device.usb_inventory.ports_used)} />
                                        <Field label="Port kosong (estimasi)" value={dash(device.usb_inventory.ports_empty)} />
                                        <Field label="Printer USB" value={dash(device.usb_inventory.printer_count)} />
                                    </dl>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {device.usb_inventory.has_removable_storage ? (
                                            <Badge
                                                variant="outline"
                                                className="border-rose-500/40 bg-rose-500/10 text-rose-700 dark:text-rose-300"
                                            >
                                                Flashdisk/storage terdeteksi ({device.usb_inventory.removable_storage_count})
                                            </Badge>
                                        ) : (
                                            <Badge
                                                variant="outline"
                                                className="border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                            >
                                                Tidak ada flashdisk
                                            </Badge>
                                        )}
                                        {device.usb_inventory.estimated ? <Badge variant="secondary">Estimasi</Badge> : null}
                                    </div>
                                    {device.usb_inventory.note ? (
                                        <p className="mt-2 text-xs text-muted-foreground">{device.usb_inventory.note}</p>
                                    ) : null}
                                    <div className="mt-4 overflow-x-auto">
                                        <table className="w-full min-w-[420px] text-left text-sm">
                                            <thead className="border-b text-xs uppercase tracking-wide text-muted-foreground">
                                                <tr>
                                                    <th className="py-2 pr-3 font-medium">Perangkat</th>
                                                    <th className="py-2 font-medium">Jenis</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {(device.usb_inventory.devices ?? []).length === 0 ? (
                                                    <tr>
                                                        <td colSpan={2} className="py-4 text-muted-foreground">
                                                            Tidak ada perangkat USB non-hub terdeteksi.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    (device.usb_inventory.devices ?? [])
                                                        .filter((d) => d.kind !== 'hub')
                                                        .map((d, idx) => (
                                                            <tr key={`${d.device_id ?? d.name}-${idx}`} className="border-b last:border-0">
                                                                <td className="py-2 pr-3">{d.name}</td>
                                                                <td className="py-2 capitalize">{d.kind}</td>
                                                            </tr>
                                                        ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">Belum ada data USB (butuh agent terbaru).</p>
                            )}
                        </Panel>

                        <Panel
                            title="Sensor suhu"
                            hint="Best-effort via ACPI thermal zone Windows — tidak semua motherboard/laptop mendukungnya."
                        >
                            {device.sensors?.supported && device.sensors.readings.length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {device.sensors.readings.map((reading, idx) => (
                                        <Badge
                                            key={`${reading.name}-${idx}`}
                                            variant="outline"
                                            className={cn(temperatureBadgeClass(reading.temperature_c))}
                                        >
                                            {reading.name}: {reading.temperature_c.toFixed(1)}°C
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {device.sensors?.note ?? 'Sensor suhu tidak didukung/belum ada data dari perangkat ini.'}
                                </p>
                            )}
                        </Panel>

                        <Panel title="Hardware">
                            {device.hardware ? (
                                <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <Field
                                        label="OS"
                                        value={`${dash(device.hardware.os)} ${dash(device.hardware.os_version)}`.trim()}
                                    />
                                    <Field label="Arch" value={dash(device.hardware.architecture)} />
                                    <Field label="CPU" value={dash(device.hardware.cpu_model)} />
                                    <Field label="Cores" value={dash(device.hardware.cpu_cores)} />
                                    <Field label="RAM total (MB)" value={dash(device.hardware.ram_total_mb)} />
                                    <Field label="Disk total (GB)" value={dash(device.hardware.disk_total_gb)} />
                                    <Field label="Manufacturer" value={dash(device.hardware.manufacturer)} />
                                    <Field label="Model" value={dash(device.hardware.model)} />
                                    <Field label="Serial" value={dash(device.hardware.serial_number)} />
                                    <Field label="Motherboard" value={dash(device.hardware.motherboard)} />
                                    <Field label="BIOS" value={dash(device.hardware.bios)} />
                                    <Field label="Timezone" value={dash(device.hardware.timezone)} />
                                    <Field label="Domain" value={dash(device.hardware.domain)} />
                                    <Field label="Username" value={dash(device.hardware.username)} />
                                </dl>
                            ) : (
                                <p className="text-sm text-muted-foreground">Belum ada data hardware.</p>
                            )}
                        </Panel>

                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
