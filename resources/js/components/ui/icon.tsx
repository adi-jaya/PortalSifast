import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface IconProps {
    iconNode?: LucideIcon | null;
    className?: string;
    strokeWidth?: number;
}

/** Thin Lucide wrapper — prefer IconWell for branded nav/action icons. */
export function Icon({ iconNode: IconComponent, className, strokeWidth = 2 }: IconProps) {
    if (!IconComponent) {
        return null;
    }

    return <IconComponent className={cn(className)} strokeWidth={strokeWidth} />;
}
