import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const statusBadgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
    {
        variants: {
            tone: {
                primary: 'border-primary/20 bg-primary-light text-primary',
                neutral: 'border-border bg-surface-2 text-ink-muted',
                normal: 'border-normal/20 bg-normal-bg text-normal',
                warning: 'border-warning/20 bg-warning-bg text-warning',
                urgent: 'border-urgent/20 bg-urgent-bg text-urgent',
                info: 'border-info/20 bg-info-bg text-info',
                'follow-up': 'border-follow-up/20 bg-follow-up-bg text-follow-up',
            },
        },
        defaultVariants: {
            tone: 'neutral',
        },
    },
);

export type StatusTone = NonNullable<VariantProps<typeof statusBadgeVariants>['tone']>;

const STATUS_TONE_MAP: Record<string, StatusTone> = {
    draft: 'neutral',
    published: 'normal',
    archived: 'warning',
    active: 'normal',
    aktif: 'normal',
    inactive: 'neutral',
    nonaktif: 'neutral',
    enabled: 'normal',
    disabled: 'neutral',
    configured: 'normal',
    unconfigured: 'warning',
    'belum lengkap': 'warning',
    'sudah lengkap': 'normal',
    ok: 'normal',
    error: 'urgent',
    featured: 'follow-up',
    pending: 'warning',
    new: 'urgent',
    responded: 'info',
    in_progress: 'warning',
    arrived: 'normal',
    resolved: 'neutral',
    cancelled: 'neutral',
    promo: 'follow-up',
    berita: 'info',
    artikel: 'info',
};

const STATUS_LABEL_MAP: Record<string, string> = {
    draft: 'Draft',
    published: 'Terbit',
    archived: 'Arsip',
    active: 'Aktif',
    inactive: 'Nonaktif',
    enabled: 'Aktif',
    disabled: 'Nonaktif',
    configured: 'Terhubung',
    unconfigured: 'Belum lengkap',
    ok: 'OK',
    error: 'Error',
    featured: 'Unggulan',
    pending: 'Menunggu',
    new: 'Baru',
    responded: 'Ditanggapi',
    in_progress: 'Diproses',
    arrived: 'Tiba',
    resolved: 'Selesai',
    cancelled: 'Dibatalkan',
};

function resolveTone(status: string): StatusTone {
    const key = status.trim().toLowerCase();

    return STATUS_TONE_MAP[key] ?? 'neutral';
}

function resolveLabel(status: string): string {
    const key = status.trim().toLowerCase();

    return STATUS_LABEL_MAP[key] ?? status;
}

type StatusBadgeProps = React.ComponentProps<'span'> &
    VariantProps<typeof statusBadgeVariants> & {
        status?: string;
        label?: string;
    };

function StatusBadge({ className, tone, status, label, children, ...props }: StatusBadgeProps) {
    const resolvedTone = tone ?? (status ? resolveTone(status) : 'neutral');
    const content = children ?? (status ? (label ?? resolveLabel(status)) : null);

    return (
        <span className={cn(statusBadgeVariants({ tone: resolvedTone }), className)} {...props}>
            {content}
        </span>
    );
}

function ActiveBadge({ active, className }: { active: boolean; className?: string }) {
    return (
        <StatusBadge tone={active ? 'normal' : 'neutral'} className={className}>
            {active ? 'Aktif' : 'Nonaktif'}
        </StatusBadge>
    );
}

function YesNoBadge({ value, yesLabel = 'Ya', noLabel = 'Tidak', className }: {
    value: boolean;
    yesLabel?: string;
    noLabel?: string;
    className?: string;
}) {
    return (
        <StatusBadge tone={value ? 'normal' : 'neutral'} className={className}>
            {value ? yesLabel : noLabel}
        </StatusBadge>
    );
}

export { ActiveBadge, resolveLabel, resolveTone, StatusBadge, statusBadgeVariants, YesNoBadge };
