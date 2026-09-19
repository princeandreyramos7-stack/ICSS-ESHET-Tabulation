import { useEffect } from "react";

/**
 * Shrinks the referenced sheet just before printing so it fits on paper, then
 * restores it afterwards. Uses CSS zoom (so layout shrinks too), which Chrome,
 * Edge and current Firefox support.
 *
 * A sheet prints on one page by default. To print on several, wrap each page's
 * content in an element with `data-print-page`: each block is then fitted to
 * its own page (and app.css puts a page break between them).
 *
 * Page box defaults to A4 landscape with 10mm margins, expressed in CSS px.
 */
export function useFitToPage(ref, { pageWidthPx = 1040, pageHeightPx = 700 } = {}) {
    useEffect(() => {
        const el = ref.current;
        if (!el) return undefined;

        const blocks = () => {
            const pages = el.querySelectorAll("[data-print-page]");
            return pages.length ? Array.from(pages) : [el];
        };

        const fit = () => {
            blocks().forEach((block) => {
                block.style.zoom = "1";
                const { scrollWidth, scrollHeight } = block;
                const scale = Math.min(1, pageWidthPx / scrollWidth, pageHeightPx / scrollHeight);
                block.style.zoom = String(Math.max(0.35, Math.floor(scale * 100) / 100));
            });
        };
        const reset = () => {
            blocks().forEach((block) => {
                block.style.zoom = "";
            });
        };

        window.addEventListener("beforeprint", fit);
        window.addEventListener("afterprint", reset);
        return () => {
            window.removeEventListener("beforeprint", fit);
            window.removeEventListener("afterprint", reset);
        };
    }, [ref, pageWidthPx, pageHeightPx]);
}
