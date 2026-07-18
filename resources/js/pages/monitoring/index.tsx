import { Head, Link, router } from '@inertiajs/react';
import { Activity, Search } from 'lucide-react';
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
    };
    stats: {
        total: number;
        online: number;
        offline: number;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Monitoring', href: '/monitoring' },
];

function formatPercent(value: string | number | null): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return `${Number(value).toFixed(1)}%`;
}

export default function MonitoringIndex({ devices, filters, stats }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(
            '/monitoring',
            {
                q: q || undefined,
                status: status === 'all' ? undefined : status,
            },
            { preserveState: true },
        );
    }

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
                            Status online/offline dan metrik CPU, RAM, Disk dari RS Agent.
                        </p>
                    </div>
                    <div className="flex gap-3 text-sm">
                        <span className="rounded-md border px-2.5 py-1">Total {stats.total}</span>
                        <span className="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-emerald-700 dark:text-emerald-300">
                            Online {stats.online}
                        </span>
                        <span className="rounded-md border px-2.5 py-1 text-muted-foreground">
                            Offline {stats.offline}
                        </span>
                    </div>
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
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[140px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua</SelectItem>
                            <SelectItem value="online">Online</SelectItem>
                            <SelectItem value="offline">Offline</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Filter
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[720px] text-left text-sm">
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
                                                className="font-medium text-foreground hover:underline"
                                            >
                                                {device.hostname || device.computer_name || device.uuid}
                                            </Link>
                                            <div className="mt-0.5 text-xs text-muted-foreground">
                                                {device.ip_address || '—'}
                                                {device.hardware?.os ? ` · ${device.hardware.os}` : ''}
                                            </div>
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    'capitalize',
                                                    device.status === 'online'
                                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {device.status}
                                            </Badge>
                                        </td>
                                        <td className="px-3 py-2.5 tabular-nums">{formatPercent(device.last_cpu_percent)}</td>
                                        <td className="px-3 py-2.5 tabular-nums">{formatPercent(device.last_ram_percent)}</td>
                                        <td className="px-3 py-2.5 tabular-nums">{formatPercent(device.last_disk_percent)}</td>
                                        <td className="px-3 py-2.5 text-muted-foreground">
                                            {device.last_seen_at
                                                ? new Date(device.last_seen_at).toLocaleString('id-ID')
                                                : '–'}
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
