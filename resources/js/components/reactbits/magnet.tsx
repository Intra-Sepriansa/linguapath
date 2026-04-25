import { motion } from 'framer-motion';
import type { PropsWithChildren } from 'react';

export function Magnet({ children }: PropsWithChildren) {
    return (
        <motion.div whileHover={{ y: -2 }} whileTap={{ scale: 0.98 }}>
            {children}
        </motion.div>
    );
}
