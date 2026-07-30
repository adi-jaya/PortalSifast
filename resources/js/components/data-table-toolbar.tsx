import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type DataTableToolbarProps = {
    children: ReactNode;
    className?: string;
};

/**
 * Flex toolbar for Search / Filter / Export / Refresh / Create.
 */
export function DataTableToolbar({ children, className }: DataTableToolbarProps) {
    return (
        <div
            className={cn(
                'data-table-toolbar flex flex-wrap items-center gap-3',
                className,
            )}
        >
            {children}
        </div>
    );
}
