import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

type DataTableProps = {
    children: ReactNode;
    toolbar?: ReactNode;
    footer?: ReactNode;
    className?: string;
};

/**
 * Soft Shell data table shell — white rounded container, spacious rows.
 * Nest a native <table> or shadcn Table inside.
 */
export function DataTable({ children, toolbar, footer, className }: DataTableProps) {
    return (
        <div className={cn('flex flex-col gap-3', className)}>
            {toolbar}
            <div className="data-table">
                <div className="data-table-scroll">{children}</div>
                {footer}
            </div>
        </div>
    );
}
