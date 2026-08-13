import { useMemo } from 'react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { toPercentNumber } from '@/lib/monitoring';

type Sample = {
    id: number;
    cpu_percent: string | number | null;
    ram_percent: string | number | null;
    disk_percent: string | number | null;
    collected_at: string;
};

type Props = {
    samples: Sample[];
};

type ChartPoint = {
    time: string;
    fullTime: string;
    cpu: number | null;
    ram: number | null;
    disk: number | null;
};

function formatAxisTime(iso: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return '–';
    }

    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatFullTime(iso: string): string {
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return '–';
    }

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function latestValue(samples: Sample[], metric: keyof Pick<Sample, 'cpu_percent' | 'ram_percent' | 'disk_percent'>): string {
    const n = toPercentNumber(samples[0]?.[metric]);
    return n === null ? '–' : `${n.toFixed(1)}%`;
}

export function MetricTrendChart({ samples }: Props) {
    const data = useMemo<ChartPoint[]>(
        () =>
            [...samples]
                .reverse()
                .map((sample) => ({
                    time: formatAxisTime(sample.collected_at),
                    fullTime: formatFullTime(sample.collected_at),
                    cpu: toPercentNumber(sample.cpu_percent),
                    ram: toPercentNumber(sample.ram_percent),
                    disk: toPercentNumber(sample.disk_percent),
                })),
        [samples],
    );

    if (samples.length === 0) {
        return (
            <div className="flex h-[280px] items-center justify-center rounded-lg border border-dashed border-border/80 bg-muted/20 text-sm text-muted-foreground">
                Belum ada sample heartbeat untuk digambar.
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-3 gap-2">
                <StatChip label="CPU" value={latestValue(samples, 'cpu_percent')} color="bg-teal-500" />
                <StatChip label="RAM" value={latestValue(samples, 'ram_percent')} color="bg-sky-500" />
                <StatChip label="Disk" value={latestValue(samples, 'disk_percent')} color="bg-amber-500" />
            </div>
            <div className="h-[280px] w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={data} margin={{ top: 8, right: 8, left: -12, bottom: 0 }}>
                        <defs>
                            <linearGradient id="metricCpuFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#14b8a6" stopOpacity={0.28} />
                                <stop offset="95%" stopColor="#14b8a6" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="metricRamFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#0ea5e9" stopOpacity={0.22} />
                                <stop offset="95%" stopColor="#0ea5e9" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="metricDiskFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="#f59e0b" stopOpacity={0.2} />
                                <stop offset="95%" stopColor="#f59e0b" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-border/70" vertical={false} />
                        <XAxis
                            dataKey="time"
                            tick={{ fontSize: 11 }}
                            className="fill-muted-foreground"
                            tickLine={false}
                            axisLine={false}
                            minTickGap={28}
                        />
                        <YAxis
                            domain={[0, 100]}
                            tick={{ fontSize: 11 }}
                            className="fill-muted-foreground"
                            tickLine={false}
                            axisLine={false}
                            tickFormatter={(value: number) => `${value}`}
                            width={36}
                        />
                        <Tooltip
                            content={({ active, payload }) => {
                                if (!active || !payload?.length) {
                                    return null;
                                }

                                const point = payload[0]?.payload as ChartPoint | undefined;

                                return (
                                    <div className="rounded-lg border border-border/80 bg-card px-3 py-2 text-xs shadow-lg">
                                        <p className="mb-1.5 font-medium text-foreground">{point?.fullTime}</p>
                                        {payload.map((entry) => (
                                            <p key={String(entry.dataKey)} className="flex items-center justify-between gap-6 tabular-nums">
                                                <span className="text-muted-foreground">{entry.name}</span>
                                                <span className="font-medium" style={{ color: entry.color }}>
                                                    {typeof entry.value === 'number' ? `${entry.value.toFixed(1)}%` : '–'}
                                                </span>
                                            </p>
                                        ))}
                                    </div>
                                );
                            }}
                        />
                        <Legend
                            verticalAlign="top"
                            align="right"
                            iconType="circle"
                            iconSize={8}
                            wrapperStyle={{ fontSize: 11, paddingBottom: 8 }}
                        />
                        <Area
                            type="monotone"
                            dataKey="cpu"
                            name="CPU"
                            stroke="#14b8a6"
                            strokeWidth={2}
                            fill="url(#metricCpuFill)"
                            connectNulls
                            dot={false}
                            activeDot={{ r: 4 }}
                        />
                        <Area
                            type="monotone"
                            dataKey="ram"
                            name="RAM"
                            stroke="#0ea5e9"
                            strokeWidth={2}
                            fill="url(#metricRamFill)"
                            connectNulls
                            dot={false}
                            activeDot={{ r: 4 }}
                        />
                        <Area
                            type="monotone"
                            dataKey="disk"
                            name="Disk"
                            stroke="#f59e0b"
                            strokeWidth={2}
                            fill="url(#metricDiskFill)"
                            connectNulls
                            dot={false}
                            activeDot={{ r: 4 }}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}

function StatChip({ label, value, color }: { label: string; value: string; color: string }) {
    return (
        <div className="rounded-lg border border-border/70 bg-muted/20 px-3 py-2">
            <div className="flex items-center gap-1.5 text-[10px] font-medium tracking-wide text-muted-foreground uppercase">
                <span className={`size-1.5 rounded-full ${color}`} />
                {label}
            </div>
            <p className="mt-0.5 font-mono text-sm font-semibold tabular-nums">{value}</p>
        </div>
    );
}
