/**
 * Shared helpers for monitoring UI (Phase 2.2).
 */

export function formatPercent(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '–';
    }

    return `${Number(value).toFixed(1)}%`;
}

export function toPercentNumber(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const n = Number(value);

    return Number.isFinite(n) ? n : null;
}

export type MetricTone = 'ok' | 'warn' | 'crit' | 'muted';

export function metricTone(value: string | number | null | undefined): MetricTone {
    const n = toPercentNumber(value);

    if (n === null) {
        return 'muted';
    }

    if (n >= 90) {
        return 'crit';
    }

    if (n >= 70) {
        return 'warn';
    }

    return 'ok';
}

export function metricBarClass(tone: MetricTone): string {
    switch (tone) {
        case 'crit':
            return 'bg-red-500';
        case 'warn':
            return 'bg-amber-500';
        case 'ok':
            return 'bg-emerald-500';
        default:
            return 'bg-muted-foreground/30';
    }
}

export function metricTextClass(tone: MetricTone): string {
    switch (tone) {
        case 'crit':
            return 'text-red-600 dark:text-red-400';
        case 'warn':
            return 'text-amber-700 dark:text-amber-400';
        case 'ok':
            return 'text-foreground';
        default:
            return 'text-muted-foreground';
    }
}

export function formatRelativeId(iso: string | null | undefined): string {
    if (!iso) {
        return '–';
    }

    const then = new Date(iso).getTime();
    const diffSec = Math.round((Date.now() - then) / 1000);

    if (!Number.isFinite(diffSec)) {
        return '–';
    }

    if (diffSec < 45) {
        return 'baru saja';
    }

    if (diffSec < 3600) {
        const m = Math.floor(diffSec / 60);

        return `${m} menit lalu`;
    }

    if (diffSec < 86400) {
        const h = Math.floor(diffSec / 3600);

        return `${h} jam lalu`;
    }

    const d = Math.floor(diffSec / 86400);

    return `${d} hari lalu`;
}

export function formatUptime(seconds: number | null | undefined): string {
    if (seconds === null || seconds === undefined || seconds < 0) {
        return '–';
    }

    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);

    if (d > 0) {
        return `${d}h ${h}j ${m}m`;
    }

    if (h > 0) {
        return `${h}j ${m}m`;
    }

    return `${m}m`;
}
