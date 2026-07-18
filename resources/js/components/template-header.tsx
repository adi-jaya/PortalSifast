import { Link, usePage } from '@inertiajs/react';
import { LogOut, Menu } from 'lucide-react';
import { logout } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { SharedData } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    onMenuClick?: () => void;
};

function getInitials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
}

export function TemplateHeader({ breadcrumbs = [], onMenuClick }: Props) {
    const { auth } = usePage<SharedData>().props;
    const title =
        breadcrumbs.length > 0
            ? breadcrumbs[breadcrumbs.length - 1].title
            : 'Dashboard';
    const user = auth.user;
    const initials = user?.name ? getInitials(user.name) : '?';

    return (
        <header className="sticky top-0 z-20 flex items-center justify-between border-b border-border bg-background/80 px-4 py-3 backdrop-blur-sm md:px-6">
            {/* Left — mobile menu + page title */}
            <div className="flex min-w-0 flex-1 items-center gap-3">
                {onMenuClick && (
                    <button
                        type="button"
                        onClick={onMenuClick}
                        className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted transition-colors hover:bg-muted/80 md:hidden"
                        aria-label="Buka menu"
                    >
                        <Menu className="h-4 w-4 text-muted-foreground" />
                    </button>
                )}

                <div className="min-w-0">
                    <h1 className="truncate text-[15px] font-semibold leading-tight text-foreground sm:text-base">
                        {title}
                    </h1>
                    {breadcrumbs.length > 1 && (
                        <nav
                            aria-label="Breadcrumb"
                            className="hidden items-center gap-1 text-xs text-muted-foreground sm:flex"
                        >
                            {breadcrumbs.map((crumb, i) => (
                                <span key={i} className="flex items-center gap-1">
                                    {i > 0 && <span className="select-none opacity-50">/</span>}
                                    {i < breadcrumbs.length - 1 ? (
                                        <Link
                                            href={crumb.href}
                                            className="truncate max-w-[120px] transition-colors hover:text-foreground"
                                        >
                                            {crumb.title}
                                        </Link>
                                    ) : (
                                        <span className="truncate max-w-[160px] font-medium text-foreground/80">
                                            {crumb.title}
                                        </span>
                                    )}
                                </span>
                            ))}
                        </nav>
                    )}
                </div>
            </div>

            {/* Right — actions + user */}
            <div className="flex shrink-0 items-center gap-2">
                <Link
                    href={logout()}
                    as="button"
                    method="post"
                    className="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                    title="Logout"
                    aria-label="Logout"
                >
                    <LogOut className="h-4 w-4" />
                </Link>

                <div
                    className="avatar-initials h-9 w-9 text-xs"
                    title={user?.name ?? user?.email ?? 'User'}
                    aria-label={`Logged in as ${user?.name ?? user?.email}`}
                >
                    {initials}
                </div>
            </div>
        </header>
    );
}
