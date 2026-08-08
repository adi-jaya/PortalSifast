import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LayoutDashboard } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { NavModuleItemLink } from '@/components/nav-module-item-link';
import { IconWell } from '@/components/icon-well';
import {
    APP_NAME,
    APP_SUBTITLE,
    buildVisibleModuleGroups,
    mainNavItems,
    settingsNavItems,
    type PortalNavPermissions,
} from '@/lib/portal-nav';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type SharedPageProps = {
    permissions?: PortalNavPermissions;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function TemplateMobileNav({ open, onOpenChange }: Props) {
    const { isCurrentUrl, currentUrl } = useCurrentUrl();
    const { permissions } = usePage<SharedPageProps>().props;
    const visibleModuleGroups = useMemo(
        () => buildVisibleModuleGroups(permissions),
        [permissions],
    );
    const activeModuleIds = useMemo(
        () =>
            visibleModuleGroups
                .filter((group) => group.items.some((item) => item.isActive(currentUrl)))
                .map((group) => group.id),
        [currentUrl, visibleModuleGroups],
    );
    const [expandedModuleIds, setExpandedModuleIds] = useState<Record<string, boolean>>({});

    function toggleModule(moduleId: string): void {
        setExpandedModuleIds((prev) => ({
            ...prev,
            [moduleId]: !(prev[moduleId] ?? activeModuleIds.includes(moduleId)),
        }));
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="left"
                className="w-64 border-sidebar-border bg-sidebar p-0 text-sidebar-foreground"
            >
                <SheetHeader className="border-b border-sidebar-border px-5 py-5">
                    <SheetTitle asChild>
                        <Link
                            href={dashboard()}
                            prefetch
                            onClick={() => onOpenChange(false)}
                            className="flex items-center gap-3 rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                        >
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                                <LayoutDashboard className="h-5 w-5 text-primary" strokeWidth={2} />
                            </div>
                            <div className="min-w-0 text-left">
                                <span className="text-sm font-semibold text-sidebar-foreground">
                                    {APP_NAME}
                                </span>
                                <p className="text-[11px] text-sidebar-muted">
                                    {APP_SUBTITLE}
                                </p>
                            </div>
                        </Link>
                    </SheetTitle>
                </SheetHeader>

                <nav className="flex-1 space-y-6 overflow-y-auto p-3 scrollbar-thin">
                    <div className="space-y-1">
                        <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                            Menu Utama
                        </p>
                        {mainNavItems.map((item) => {
                            const isActive = item.isActive(currentUrl);
                            return (
                                <Link
                                    key={item.id}
                                    href={item.href}
                                    prefetch
                                    onClick={() => onOpenChange(false)}
                                    className={cn(
                                        'flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2.5 text-sm transition-colors duration-200',
                                        isActive
                                            ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground shadow-sm'
                                            : 'text-sidebar-foreground/90 hover:bg-white/15 hover:text-sidebar-foreground',
                                    )}
                                >
                                    <IconWell
                                        icon={item.icon}
                                        size="sm"
                                        variant={isActive ? 'sidebar-active' : 'sidebar'}
                                    />
                                    {item.label}
                                </Link>
                            );
                        })}
                    </div>

                    <div className="space-y-1">
                        <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                            Modul
                        </p>
                        {visibleModuleGroups.map((group) => {
                            const hasActiveChild = group.items.some((item) => item.isActive(currentUrl));
                            const isExpanded = expandedModuleIds[group.id] ?? hasActiveChild;

                            return (
                                <div key={group.id} className="space-y-1">
                                    <button
                                        type="button"
                                        onClick={() => toggleModule(group.id)}
                                        className={cn(
                                            'flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2.5 text-left text-sm transition-colors duration-200',
                                            hasActiveChild
                                                ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground shadow-sm'
                                                : 'text-sidebar-foreground/90 hover:bg-white/15 hover:text-sidebar-foreground',
                                        )}
                                    >
                                        <IconWell
                                            icon={group.icon}
                                            size="sm"
                                            variant={hasActiveChild ? 'sidebar-active' : 'sidebar'}
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate">{group.label}</span>
                                            {group.hint ? (
                                                <span className="block truncate text-[10px] font-normal text-sidebar-muted">
                                                    {group.hint}
                                                </span>
                                            ) : null}
                                        </span>
                                        <ChevronDown
                                            className={cn(
                                                'h-4 w-4 shrink-0 transition-transform duration-200',
                                                isExpanded ? 'rotate-180' : '',
                                            )}
                                            strokeWidth={2}
                                        />
                                    </button>

                                    {isExpanded && (
                                        <div className="space-y-1 pl-3">
                                            {group.items.map((item) => {
                                                const isItemActive = item.isActive(currentUrl);
                                                return (
                                                    <NavModuleItemLink
                                                        key={item.id}
                                                        href={item.href}
                                                        icon={item.icon}
                                                        label={item.label}
                                                        isActive={isItemActive}
                                                        fullPage={item.fullPage}
                                                        onNavigate={() => onOpenChange(false)}
                                                    />
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    <div className="space-y-1">
                        <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider text-sidebar-muted">
                            Pengaturan
                        </p>
                        {settingsNavItems.map((item) => (
                            <Link
                                key={item.id}
                                href={item.href}
                                prefetch
                                onClick={() => onOpenChange(false)}
                                className={cn(
                                    'flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2.5 text-sm transition-colors duration-200',
                                    isCurrentUrl(item.href)
                                        ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground shadow-sm'
                                        : 'text-sidebar-foreground/90 hover:bg-white/15 hover:text-sidebar-foreground',
                                )}
                            >
                                <IconWell
                                    icon={item.icon}
                                    size="sm"
                                    variant={isCurrentUrl(item.href) ? 'sidebar-active' : 'sidebar'}
                                />
                                {item.label}
                            </Link>
                        ))}
                    </div>
                </nav>
            </SheetContent>
        </Sheet>
    );
}
