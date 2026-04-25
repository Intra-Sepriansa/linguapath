import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

export function MagicBento({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3',
                className,
            )}
        >
            {children}
        </div>
    );
}
