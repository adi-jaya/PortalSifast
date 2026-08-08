import { cn } from '@/lib/utils';
import { formatPercent, metricBarClass, metricTextClass, metricTone } from '@/lib/monitoring';

type Props = {
    label?: string;
    value: string | number | null | undefined;
    className?: string;
};

export function MetricBar({ label, value, className }: Props) {
    const tone = metricTone(value);
    const n = Math.min(100, Math.max(0, Number(value) || 0));
    const hasValue = value !== null && value !== undefined && value !== '';

    return (
        <div className={cn('min-w-[72px]', className)}>
            {label ? <div className="mb-0.5 text-[10px] uppercase tracking-wide text-muted-foreground">{label}</div> : null}
            <div className="flex items-center gap-2">
                <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                    <div
                        className={cn('h-full rounded-full transition-[width]', metricBarClass(tone))}
                        style={{ width: hasValue ? `${n}%` : '0%' }}
                    />
                </div>
                <span className={cn('w-12 text-right text-xs tabular-nums', metricTextClass(tone))}>
                    {formatPercent(hasValue ? value : null)}
                </span>
            </div>
        </div>
    );
}
