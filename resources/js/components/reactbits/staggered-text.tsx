import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';

export function StaggeredText({
    text,
    className,
}: {
    text: string;
    className?: string;
}) {
    const words = text.split(' ');

    return (
        <span className={cn('inline-flex flex-wrap gap-x-2', className)}>
            {words.map((word, index) => (
                <motion.span
                    key={`${word}-${index}`}
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                        delay: index * 0.025,
                        duration: 0.25,
                        ease: 'easeOut',
                    }}
                >
                    {word}
                </motion.span>
            ))}
        </span>
    );
}
