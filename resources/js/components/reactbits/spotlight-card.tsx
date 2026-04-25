import { motion } from 'framer-motion';
import type { PropsWithChildren } from 'react';
import { cn } from '@/lib/utils';

export function SpotlightCard({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
            className={cn(
                'group relative overflow-hidden rounded-lg border border-violet-100 bg-white/95 shadow-[0_18px_55px_rgba(79,70,229,0.08)] dark:border-indigo-900/60 dark:bg-slate-950/95',
                className,
            )}
        >
            <div className="absolute inset-x-0 top-0 h-px bg-indigo-500/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            <div className="relative">{children}</div>
        </motion.div>
    );
}
