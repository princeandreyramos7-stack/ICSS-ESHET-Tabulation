import { useEffect, useState } from "react";
import { router } from "@inertiajs/react";

import { Spinner } from "@/Components/ui/spinner";

/**
 * Full-page loading overlay shown while Inertia is fetching a page or
 * submitting a form. Waits `delay` ms before appearing so quick requests
 * never flash it; on slow connections it reassures the user.
 */
export default function PageLoader({ delay = 250, label = "Loading..." }) {
    const [visible, setVisible] = useState(false);
    const [text, setText] = useState(label);

    useEffect(() => {
        let timer = null;

        const offStart = router.on("start", (event) => {
            const visit = event.detail.visit;
            // Partial reloads (e.g. dashboard auto-refresh) are silent.
            if (visit.only?.length > 0) return;
            setText(visit.method && visit.method.toLowerCase() !== "get" ? "Saving..." : label);
            clearTimeout(timer);
            timer = setTimeout(() => setVisible(true), delay);
        });
        const offFinish = router.on("finish", () => {
            clearTimeout(timer);
            setVisible(false);
        });

        return () => {
            clearTimeout(timer);
            offStart();
            offFinish();
        };
    }, [delay, label]);

    if (!visible) return null;

    return (
        <div
            role="status"
            aria-live="polite"
            className="fixed inset-0 z-[60] flex items-center justify-center bg-white/60 backdrop-blur-[2px] animate-in fade-in duration-200"
        >
            <div className="flex items-center gap-3 rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-800 shadow-xl">
                <Spinner className="size-5 text-emerald-700" />
                {text}
            </div>
        </div>
    );
}
