import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LayoutDashboard } from 'lucide-react';
import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { NavModuleItemLink } from '@/components/nav-module-item-link';
import { IconWell } from '@/components/icon-well';
import { useCurrentUrl } from '@/hooks/use-current-url';
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

const SIDEBAR_NAV_SCROLL_KEY = 'portal.sidebar.navScroll';
const SIDEBAR_EXPANDED_KEY = 'portal.sidebar.expandedModules';

function readExpandedModules(): Record<string, boolean> {
    try {
        const raw = sessionStorage.getItem(SIDEBAR_EXPANDED_KEY);
        if (!raw) {
            return {};
        }
        const parsed = JSON.parse(raw) as unknown;

        return parsed && typeof parsed === 'object' ? (parsed as Record<string, boolean>) : {};
    } catch {
        return {};
    }
}

type SharedPageProps = {
    permissions?: PortalNavPermissions;
};

export function TemplateSidebar() {
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
    const [expandedModuleIds, setExpandedModuleIds] = useState<Record<string, boolean>>(readExpandedModules);
    const navRef = useRef<HTMLElement>(null);

    function toggleModule(moduleId: string): void {
        setExpandedModuleIds((prev) => {
            const next = {
                ...prev,
                [moduleId]: !(prev[moduleId] ?? activeModuleIds.includes(moduleId)),
            };
            try {
                sessionStorage.setItem(SIDEBAR_EXPANDED_KEY, JSON.stringify(next));
            } catch {
                // ignore quota / private mode
            }

            return next;
        });
    }

    useEffect(() => {
        const el = navRef.current;
        if (!el) {
            return;
        }

        const onScroll = (): void => {
            try {
                sessionStorage.setItem(SIDEBAR_NAV_SCROLL_KEY, String(el.scrollTop));
            } catch {
                // ignore
            }
        };

        el.addEventListener('scroll', onScroll, { passive: true });

        return () => el.removeEventListener('scroll', onScroll);
    }, []);

    useLayoutEffect(() => {
        const el = navRef.current;
        if (!el) {
            return;
        }

        const saved = Number(sessionStorage.getItem(SIDEBAR_NAV_SCROLL_KEY) ?? '0');
        if (Number.isFinite(saved) && saved > 0) {
            el.scrollTop = saved;
        }

        const active = el.querySelector<HTMLElement>('[data-sidebar-active="true"]');
        active?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }, [currentUrl]);

    return (
        <aside className="fixed left-0 top-0 z-30 hidden h-screen w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground md:flex">
            <div className="border-b border-sidebar-border px-5 py-5">
                <Link
                    href={dashboard()}
                    prefetch
                    className="flex items-center gap-3 rounded-xl outline-none transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                >
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                        <LayoutDashboard className="h-5 w-5 text-primary" strokeWidth={2} />
                    </div>
                    <div className="min-w-0">
                        <h1 className="truncate text-sm font-semibold text-sidebar-foreground">
                            {APP_NAME}
                        </h1>
                        <p className="truncate text-[11px] text-sidebar-muted">
                            {APP_SUBTITLE}
                        </p>
                    </div>
                </Link>
            </div>

            <nav ref={navRef} className="flex-1 space-y-6 overflow-y-auto p-3 scrollbar-thin">
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
                                data-sidebar-active={isActive ? 'true' : undefined}
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
                            data-sidebar-active={isCurrentUrl(item.href) ? 'true' : undefined}
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
        </aside>
    );
}
