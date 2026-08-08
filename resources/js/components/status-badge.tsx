import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const statusBadgeVariants = cva(
    'inline-flex h-6 items-center rounded-full border px-2.5 py-1 text-xs font-semibold whitespace-nowrap',
    {
        variants: {
            tone: {
                primary: 'border-transparent bg-info-bg text-info',
                neutral: 'border-transparent bg-gray-100 text-gray-700',
                normal: 'border-transparent bg-normal-bg text-normal',
                warning: 'border-transparent bg-warning-bg text-warning',
                urgent: 'border-transparent bg-urgent-bg text-urgent',
                info: 'border-transparent bg-info-bg text-info',
                'follow-up': 'border-transparent bg-follow-up-bg text-follow-up',
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
