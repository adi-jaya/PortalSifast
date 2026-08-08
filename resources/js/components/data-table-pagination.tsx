import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type DataTablePaginationProps = {
    links: PaginationLink[];
    className?: string;
};

/**
 * Table pagination — 40px height, 10px radius; current page primary filled.
 */
export function DataTablePagination({ links, className }: DataTablePaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div
            className={cn(
                'data-table-pagination flex h-10 flex-wrap items-center justify-center gap-2 border-t border-border bg-card px-4',
                className,
            )}
        >
            {links.map((link, index) => {
                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm text-muted-foreground"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Button
                        key={`${link.label}-${index}`}
                        size="sm"
                        variant={link.active ? 'default' : 'ghost'}
                        className={cn(
                            'h-8 min-w-8 rounded-[10px] px-2.5 text-sm font-semibold',
                            !link.active && 'text-gray-700',
                        )}
                        asChild
                    >
                        <Link href={link.url} preserveState>
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                    </Button>
                );
            })}
        </div>
    );
}
