import { cn } from '@/lib/utils';
import { metricBarClass, metricTone, toPercentNumber } from '@/lib/monitoring';

type Sample = {
    cpu_percent: string | number | null;
    ram_percent: string | number | null;
    disk_percent: string | number | null;
};

type Props = {
    samples: Sample[];
    metric: 'cpu_percent' | 'ram_percent' | 'disk_percent';
    className?: string;
};

export function MetricSparkline({ samples, metric, className }: Props) {
    // Oldest → newest left to right
    const points = [...samples].reverse();

    if (points.length === 0) {
        return <p className="text-sm text-muted-foreground">Belum ada data untuk grafik.</p>;
    }

    return (
        <div className={cn('flex h-16 items-end gap-0.5', className)} aria-hidden>
            {points.map((sample, index) => {
                const raw = sample[metric];
                const n = toPercentNumber(raw) ?? 0;
                const tone = metricTone(raw);

                return (
                    <div
                        key={`${metric}-${index}`}
                        className={cn('min-w-0 flex-1 rounded-t-sm', metricBarClass(tone))}
                        style={{ height: `${Math.max(4, n)}%` }}
                        title={`${n.toFixed(1)}%`}
                    />
                );
            })}
        </div>
    );
}
