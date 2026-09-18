import { useEffect, useState } from "react";

/**
 * Live countdown to an ISO date. Returns { days, hours, minutes, seconds, done }.
 */
export function useCountdown(targetIso) {
    const compute = () => {
        const target = new Date(targetIso).getTime();
        const diff = Number.isFinite(target) ? target - Date.now() : 0;
        if (diff <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0, done: true };
        return {
            days: Math.floor(diff / 86400000),
            hours: Math.floor((diff / 3600000) % 24),
            minutes: Math.floor((diff / 60000) % 60),
            seconds: Math.floor((diff / 1000) % 60),
            done: false,
        };
    };

    const [value, setValue] = useState(compute);

    useEffect(() => {
        const id = setInterval(() => setValue(compute()), 1000);
        return () => clearInterval(id);
    }, [targetIso]);

    return value;
}
