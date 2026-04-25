import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type AppLogoIconProps = Omit<ImgHTMLAttributes<HTMLImageElement>, 'src'>;

export default function AppLogoIcon({
    className,
    alt = 'LinguaPath',
    ...props
}: AppLogoIconProps) {
    return (
        <img
            {...props}
            src="/logo.png"
            alt={alt}
            draggable={false}
            decoding="async"
            className={cn('object-contain', className)}
        />
    );
}
