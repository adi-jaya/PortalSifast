import { MetricBar } from '@/components/monitoring/metric-bar';
import { formatRelativeId, formatUptime } from '@/lib/monitoring';

type DeviceMetrics = {
    uuid: string;
    hostname: string | null;
    computer_name: string | null;
    ip_address: string | null;
    mac_address: string | null;
    last_seen_at: string | null;
    uptime_seconds: number | null;
    last_cpu_percent: string | number | null;
    last_ram_percent: string | number | null;
    last_disk_percent: string | number | null;
};

type Props = {
    device: DeviceMetrics;
};

function dash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return String(value);
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-[10px] tracking-wide text-muted-foreground uppercase">{label}</dt>
            <dd className="mt-0.5 text-sm">{value}</dd>
        </div>
    );
}

export function MetricsSidebar({ device }: Props) {
    return (
        <aside className="flex flex-col gap-4">
            <div className="rounded-xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <h2 className="mb-3 text-xs font-semibold tracking-wide text-teal-700 uppercase dark:text-teal-400">
                    Metrik langsung
                </h2>
                <div className="grid gap-3">
                    <MetricBar label="CPU" value={device.last_cpu_percent} />
                    <MetricBar label="RAM" value={device.last_ram_percent} />
                    <MetricBar label="Disk" value={device.last_disk_percent} />
                </div>
            </div>

            <div className="rounded-xl border border-border/80 bg-card p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <h2 className="mb-3 text-xs font-semibold tracking-wide text-teal-700 uppercase dark:text-teal-400">
                    Identitas
                </h2>
                <dl className="grid gap-3">
                    <Field label="Hostname" value={dash(device.hostname)} />
                    <Field label="Computer name" value={dash(device.computer_name)} />
                    <Field label="IP" value={dash(device.ip_address)} />
                    <Field label="MAC" value={dash(device.mac_address)} />
                    <div>
                        <dt className="text-[10px] tracking-wide text-muted-foreground uppercase">UUID</dt>
                        <dd className="mt-0.5 font-mono text-xs break-all">{device.uuid}</dd>
                    </div>
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
            </div>
        </aside>
    );
}
