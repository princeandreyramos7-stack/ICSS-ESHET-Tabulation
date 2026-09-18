import { useEffect } from "react";

/**
 * Shrinks the referenced element just before printing so it fits on a single
 * page, then restores it afterwards. Uses CSS zoom (so layout shrinks too),
 * which Chrome, Edge and current Firefox support.
 *
 * Page box defaults to A4 landscape with 10mm margins, expressed in CSS px.
 */
export function useFitToPage(ref, { pageWidthPx = 1040, pageHeightPx = 700 } = {}) {
    useEffect(() => {
        const el = ref.current;
        if (!el) return undefined;

        const fit = () => {
            el.style.zoom = "1";
            const { scrollWidth, scrollHeight } = el;
            const scale = Math.min(1, pageWidthPx / scrollWidth, pageHeightPx / scrollHeight);
            el.style.zoom = String(Math.max(0.35, Math.floor(scale * 100) / 100));
        };
        const reset = () => {
            el.style.zoom = "";
        };

        window.addEventListener("beforeprint", fit);
        window.addEventListener("afterprint", reset);
        return () => {
            window.removeEventListener("beforeprint", fit);
            window.removeEventListener("afterprint", reset);
        };
    }, [ref, pageWidthPx, pageHeightPx]);
}
