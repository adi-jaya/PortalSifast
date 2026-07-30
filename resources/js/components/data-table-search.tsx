import { Search } from 'lucide-react';
import type { ComponentProps } from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type DataTableSearchProps = Omit<ComponentProps<'input'>, 'type'> & {
    containerClassName?: string;
};

/**
 * Table search field — 44px height, 12px radius, search icon left.
 */
export function DataTableSearch({
    className,
    containerClassName,
    placeholder = 'Search...',
    ...props
}: DataTableSearchProps) {
    return (
        <div className={cn('relative min-w-0 flex-1', containerClassName)}>
            <Search
                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden
            />
            <Input
                type="search"
                placeholder={placeholder}
                className={cn(
                    'data-table-search h-11 rounded-xl border-border bg-card pl-10 shadow-none',
                    className,
                )}
                {...props}
            />
        </div>
    );
}
