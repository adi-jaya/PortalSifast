import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

type DataTableSkeletonProps = {
    rows?: number;
    columns?: number;
    className?: string;
};

/**
 * Loading skeleton — 5 pulse rows by default.
 */
export function DataTableSkeleton({
    rows = 5,
    columns = 5,
    className,
}: DataTableSkeletonProps) {
    return (
        <div className={cn('data-table', className)} aria-busy="true" aria-live="polite">
            <div className="data-table-scroll">
                <table className="w-full">
                    <thead>
                        <tr>
                            {Array.from({ length: columns }).map((_, index) => (
                                <th key={index}>
                                    <Skeleton className="h-3.5 w-24" />
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {Array.from({ length: rows }).map((_, rowIndex) => (
                            <tr key={rowIndex}>
                                {Array.from({ length: columns }).map((_, colIndex) => (
                                    <td key={colIndex}>
                                        <Skeleton className="h-4 w-full max-w-[12rem]" />
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
