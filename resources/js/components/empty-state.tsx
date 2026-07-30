import { Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    icon?: React.ReactNode;
    title: string;
    description?: string;
    action?: React.ReactNode;
    className?: string;
};

export function EmptyState({
    icon,
    title,
    description,
    action,
    className,
}: Props) {
    return (
        <div
            data-empty-state
            className={cn(
                'flex flex-col items-center justify-center px-6 py-12 text-center',
                className
            )}
        >
            <div className="mb-4 flex size-16 items-center justify-center rounded-full bg-gray-50 text-muted-foreground dark:bg-muted">
                {icon ?? <Inbox className="size-8" />}
            </div>
            <h3 className="text-base font-semibold text-foreground">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm font-normal text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
