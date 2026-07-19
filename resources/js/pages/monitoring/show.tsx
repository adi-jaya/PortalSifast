import { Head, Link, router, usePoll } from '@inertiajs/react';
import { ArrowLeft, RefreshCw } from 'lucide-react';
import type { ReactNode } from 'react';
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
    hardware: Hardware | null;
    aset: { id: number; kode_aset: string; no_seri: string | null } | null;
};

type Props = {
    device: Device;
    recentSamples: Sample[];
};

function dash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

function Field({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 text-sm">{value}</dd>
        </div>
    );
}

export default function MonitoringShow({ device, recentSamples }: Props) {
    const title = device.hostname || device.computer_name || device.uuid;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Monitoring', href: '/monitoring' },
        { title, href: `/monitoring/${device.id}` },
    ];

    usePoll(30000, {
        only: ['device', 'recentSamples'],
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Monitoring · ${title}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                            <Link href="/monitoring">
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                        <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
                        <p className="mt-1 font-mono text-xs text-muted-foreground">{device.uuid}</p>
                        <div className="mt-2 flex flex-wrap gap-2">
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
                            <span className="self-center text-xs text-muted-foreground">
                                Last seen {formatRelativeId(device.last_seen_at)}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload({ only: ['device', 'recentSamples'] })}
                        >
                            <RefreshCw className="size-3.5" />
                            Refresh
                        </Button>
                        <div className="grid w-full min-w-[260px] gap-2 sm:min-w-[320px]">
                            <div className="rounded-lg border px-3 py-2">
                                <MetricBar label="CPU" value={device.last_cpu_percent} />
                            </div>
                            <div className="rounded-lg border px-3 py-2">
                                <MetricBar label="RAM" value={device.last_ram_percent} />
                            </div>
                            <div className="rounded-lg border px-3 py-2">
                                <MetricBar label="Disk" value={device.last_disk_percent} />
                            </div>
                        </div>
                    </div>
                </div>

                <section className="rounded-lg border p-4">
                    <h2 className="text-sm font-semibold">Tren singkat (sample terbaru)</h2>
                    <p className="mt-1 text-xs text-muted-foreground">Kiri = lebih lama · Kanan = terbaru · Warna: hijau &lt;70%, amber ≥70%, merah ≥90%</p>
                    <div className="mt-4 grid gap-4 md:grid-cols-3">
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
                </section>

                <section className="rounded-lg border p-4">
                    <h2 className="text-sm font-semibold">Identitas</h2>
                    <dl className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
                        <Field
                            label="Aset terkait"
                            value={
                                device.aset ? (
                                    <Link href={`/aset/${device.aset.kode_aset}`} className="font-medium underline-offset-2 hover:underline">
                                        {device.aset.kode_aset}
                                        {device.aset.no_seri ? ` · SN ${device.aset.no_seri}` : ''}
                                    </Link>
                                ) : (
                                    '–'
                                )
                            }
                        />
                    </dl>
                </section>

                <section className="rounded-lg border p-4">
                    <h2 className="text-sm font-semibold">Hardware</h2>
                    {device.hardware ? (
                        <dl className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
                        <p className="mt-2 text-sm text-muted-foreground">Belum ada data hardware.</p>
                    )}
                </section>

                <section className="rounded-lg border p-4">
                    <h2 className="text-sm font-semibold">Sample terbaru</h2>
                    <div className="mt-3 overflow-x-auto">
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
                </section>
            </div>
        </AppLayout>
    );
}
