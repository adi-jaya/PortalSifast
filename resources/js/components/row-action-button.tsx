import { Slot } from '@radix-ui/react-slot';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

type RowActionButtonProps = ComponentProps<'button'> & {
    asChild?: boolean;
};

/**
 * Ghost icon button for table row actions — 36×36, radius 10px.
 */
export function RowActionButton({
    className,
    asChild = false,
    type = 'button',
    ...props
}: RowActionButtonProps) {
    const Comp = asChild ? Slot : 'button';

    return (
        <Comp
            type={asChild ? undefined : type}
            className={cn(
                'inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-[10px]',
                'text-gray-700 transition-colors duration-150 ease-in-out',
                'hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                'disabled:pointer-events-none disabled:opacity-50',
                '[&_svg]:size-4',
                className,
            )}
            {...props}
        />
    );
}
