import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type IconWellVariant =
    | 'brand'
    | 'soft'
    | 'sidebar'
    | 'sidebar-active'
    | 'danger'
    | 'muted'
    | 'on-brand';

type IconWellSize = 'sm' | 'md' | 'lg';

const sizeClasses: Record<IconWellSize, { well: string; icon: string }> = {
    sm: { well: 'size-7 rounded-lg', icon: 'size-3.5' },
    md: { well: 'size-8 rounded-xl', icon: 'size-4' },
    lg: { well: 'size-10 rounded-xl', icon: 'size-5' },
};

const variantClasses: Record<IconWellVariant, string> = {
    brand: 'bg-primary text-primary-foreground',
    soft: 'bg-secondary text-primary',
    sidebar: 'bg-white/15 text-white',
    'sidebar-active': 'bg-white text-primary',
    danger: 'bg-destructive text-destructive-foreground',
    muted: 'bg-muted text-muted-foreground',
    'on-brand': 'bg-white/20 text-white',
};

type IconWellProps = {
    icon: LucideIcon;
    variant?: IconWellVariant;
    size?: IconWellSize;
    className?: string;
    iconClassName?: string;
    strokeWidth?: number;
};

/**
 * Consistent Lucide icon container — solid brand wells.
 * Use SVG icons only; keep stroke width uniform across the shell.
 */
export function IconWell({
    icon: Icon,
    variant = 'brand',
    size = 'md',
    className,
    iconClassName,
    strokeWidth = 2,
}: IconWellProps) {
    const sizing = sizeClasses[size];

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center',
                sizing.well,
                variantClasses[variant],
                className,
            )}
            aria-hidden
        >
            <Icon
                className={cn(sizing.icon, iconClassName)}
                strokeWidth={strokeWidth}
            />
        </span>
    );
}
