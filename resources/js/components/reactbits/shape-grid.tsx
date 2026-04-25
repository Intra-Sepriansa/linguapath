import { cn } from '@/lib/utils';

export function ShapeGrid({ className }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={cn(
                'pointer-events-none absolute inset-0 overflow-hidden opacity-70',
                className,
            )}
        >
            <div className="absolute inset-0 bg-[linear-gradient(to_right,rgba(79,70,229,0.10)_1px,transparent_1px),linear-gradient(to_bottom,rgba(16,185,129,0.10)_1px,transparent_1px)] bg-[size:52px_52px]" />
            <div className="absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-indigo-100/60 to-transparent dark:from-indigo-950/40" />
        </div>
    );
}
