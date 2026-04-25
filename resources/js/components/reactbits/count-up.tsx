import { useEffect, useState } from 'react';

export function CountUp({
    value,
    suffix = '',
}: {
    value: number;
    suffix?: string;
}) {
    const [display, setDisplay] = useState(0);

    useEffect(() => {
        const duration = 500;
        const start = performance.now();

        const frame = (time: number) => {
            const progress = Math.min((time - start) / duration, 1);
            setDisplay(Math.round(value * progress));

            if (progress < 1) {
                requestAnimationFrame(frame);
            }
        };

        requestAnimationFrame(frame);
    }, [value]);

    return (
        <span>
            {display}
            {suffix}
        </span>
    );
}
