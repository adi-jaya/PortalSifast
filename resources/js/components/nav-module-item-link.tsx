import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { IconWell } from '@/components/icon-well';
import { cn } from '@/lib/utils';

type NavModuleItemLinkProps = {
    href: string;
    icon: LucideIcon;
    label: string;
    isActive: boolean;
    fullPage?: boolean;
    onNavigate?: () => void;
    className?: string;
};

export function NavModuleItemLink({
    href,
    icon,
    label,
    isActive,
    fullPage = false,
    onNavigate,
    className,
}: NavModuleItemLinkProps) {
    const classes = cn(
        'flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2 text-sm transition-colors duration-200',
        isActive
            ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground shadow-sm'
            : 'text-sidebar-foreground/90 hover:bg-white/15 hover:text-sidebar-foreground',
        className,
    );

    const content = (
        <>
            <IconWell
                icon={icon}
                size="sm"
                variant={isActive ? 'sidebar-active' : 'sidebar'}
            />
            {label}
        </>
    );

    if (fullPage) {
        return (
            <a
                href={href}
                className={classes}
                onClick={onNavigate}
                data-sidebar-active={isActive ? 'true' : undefined}
            >
                {content}
            </a>
        );
    }

    return (
        <Link
            href={href}
            prefetch
            className={classes}
            onClick={onNavigate}
            data-sidebar-active={isActive ? 'true' : undefined}
        >
            {content}
        </Link>
    );
}
