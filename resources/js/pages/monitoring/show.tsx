import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import { ArrowLeft, RefreshCw } from 'lucide-react';
import { useEffect, type ReactNode } from 'react';
import { AssetLinkPicker } from '@/components/monitoring/asset-link-picker';
import { MetricBar } from '@/components/monitoring/metric-bar';
import { MetricSparkline } from '@/components/monitoring/metric-sparkline';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

type Props = {
    device: Device;
    recentSamples: Sample[];
    linkableAssets: LinkableAsset[];
};

const NONE_ASET = '__none__';

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

export default function MonitoringShow({ device, recentSamples, linkableAssets }: Props) {
    const title = device.hostname || device.computer_name || device.uuid;
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

    usePoll(30000, {
        only: ['device', 'recentSamples', 'linkableAssets'],
    });

    const submitAset = () => {
        patch(`/monitoring/${device.id}/aset`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Monitoring · ${title}`} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-5 p-4 md:p-6">
                <header className="flex flex-wrap items-start justify-between gap-4 border-b border-border/70 pb-5">
                    <div className="min-w-0">
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                            <Link href="/monitoring">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                        <p className="text-[11px] font-medium tracking-[0.16em] text-teal-700 uppercase dark:text-teal-400">
                            Detail perangkat
                        </p>
                        <h1 className="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">{title}</h1>
                        <p className="mt-1 font-mono text-xs text-muted-foreground">{device.uuid}</p>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
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
                            {device.agent_version ? (
                                <Badge variant="secondary">Agent {device.agent_version}</Badge>
                            ) : null}
                            <span className="text-xs text-muted-foreground">
                                Last seen {formatRelativeId(device.last_seen_at)} · Uptime {formatUptime(device.uptime_seconds)}
                            </span>
                        </div>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:min-w-[280px]">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="self-end"
                            onClick={() => router.reload({ only: ['device', 'recentSamples', 'linkableAssets'] })}
                        >
                            <RefreshCw className="size-3.5" />
                            Refresh
                        </Button>
                        <div className="grid gap-2 rounded-xl border border-border/80 bg-card p-3">
                            <MetricBar label="CPU" value={device.last_cpu_percent} />
                            <MetricBar label="RAM" value={device.last_ram_percent} />
                            <MetricBar label="Disk" value={device.last_disk_percent} />
                        </div>
                    </div>
                </header>

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
                    title="Tren singkat"
                    hint="Kiri = lebih lama · Kanan = terbaru · Hijau &lt;70% · Amber ≥70% · Merah ≥90%"
                >
                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <div className="mb-1 text-xs font-medium text-muted-foreground">CPU</div>
                            <MetricSparkline samples={recentSamples} metric="cpu_percent" />
                        </div>
                        <div>
                            <div className="mb-1 text-xs font-medium text-muted-foreground">RAM</div>
                            <MetricSparkline samples={recentSamples} metric="ram_percent" />
                        </div>
                        <div>
                            <div className="mb-1 text-xs font-medium text-muted-foreground">Disk</div>
                            <MetricSparkline samples={recentSamples} metric="disk_percent" />
                        </div>
                    </div>
                </Panel>

                <Panel title="Identitas">
                    <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Field label="Hostname" value={dash(device.hostname)} />
                        <Field label="Computer name" value={dash(device.computer_name)} />
                        <Field label="IP" value={dash(device.ip_address)} />
                        <Field label="MAC" value={dash(device.mac_address)} />
                        <Field
                            label="Last seen"
                            value={
                                device.last_seen_at
                                    ? `${formatRelativeId(device.last_seen_at)} · ${new Date(device.last_seen_at).toLocaleString('id-ID')}`
                                    : '–'
                            }
                        />
                        <Field label="Uptime" value={formatUptime(device.uptime_seconds)} />
                    </dl>
                </Panel>

                <Panel title="Software kritis" hint="Tools remote / sync untuk dukungan jarak jauh.">
                    <div className="grid gap-2 sm:grid-cols-3">
                        {(device.critical_software ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground sm:col-span-3">Belum ada data (butuh agent terbaru).</p>
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
                                    {item.detail ? <p className="mt-1 truncate text-xs text-muted-foreground">{item.detail}</p> : null}
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
                            <Field label="OS" value={`${dash(device.hardware.os)} ${dash(device.hardware.os_version)}`.trim()} />
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

                <Panel title="Sample terbaru">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[480px] text-left text-sm">
                            <thead className="border-b text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="py-2 pr-3 font-medium">Waktu</th>
                                    <th className="py-2 pr-3 font-medium">CPU</th>
                                    <th className="py-2 pr-3 font-medium">RAM</th>
                                    <th className="py-2 font-medium">Disk</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentSamples.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="py-6 text-muted-foreground">
                                            Belum ada sample heartbeat.
                                        </td>
                                    </tr>
                                ) : (
                                    recentSamples.slice(0, 20).map((sample) => (
                                        <tr key={sample.id} className="border-b last:border-0">
                                            <td className="py-2 pr-3 text-muted-foreground">
                                                {new Date(sample.collected_at).toLocaleString('id-ID')}
                                            </td>
                                            <td className="py-2 pr-3">
                                                <MetricBar value={sample.cpu_percent} />
                                            </td>
                                            <td className="py-2 pr-3">
                                                <MetricBar value={sample.ram_percent} />
                                            </td>
                                            <td className="py-2">
                                                <MetricBar value={sample.disk_percent} />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Panel>
            </div>
        </AppLayout>
    );
}
