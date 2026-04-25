import { motion } from 'framer-motion';
import type { PropsWithChildren } from 'react';

export function FadeContent({ children }: PropsWithChildren) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
        >
            {children}
        </motion.div>
    );
}
