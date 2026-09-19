import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react";

import { Button } from "@/Components/ui/button";
import { Select } from "@/Components/ui/select";
import { PAGE_SIZES } from "@/hooks/use-pagination";

/** Page numbers to show: first, last, and a window around the current page, with "…" gaps. */
function pageList(page, pageCount) {
    if (pageCount <= 7) return Array.from({ length: pageCount }, (_, i) => i + 1);
    const pages = new Set([1, pageCount, page - 1, page, page + 1]);
    if (page <= 3) [2, 3, 4].forEach((p) => pages.add(p));
    if (page >= pageCount - 2) [pageCount - 3, pageCount - 2, pageCount - 1].forEach((p) => pages.add(p));
    const sorted = [...pages].filter((p) => p >= 1 && p <= pageCount).sort((a, b) => a - b);
    const out = [];
    sorted.forEach((p, i) => {
        if (i > 0 && p - sorted[i - 1] > 1) out.push("gap-" + p);
        out.push(p);
    });
    return out;
}

/**
 * Footer for paginated admin tables: "Showing a-b of n", rows-per-page, and page controls.
 * Pass the object returned by usePagination() as `pager`.
 */
export default function Pagination({ pager, noun = "rows", className = "" }) {
    const { page, setPage, pageSize, setPageSize, pageCount, total, from, to } = pager;

    return (
        <div
            className={`flex flex-col gap-3 border-t bg-gray-50 px-4 py-2.5 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between ${className}`}
        >
            <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span>
                    {total === 0 ? `No ${noun}` : (
                        <>
                            Showing <span className="font-semibold text-gray-900">{from}</span>-
                            <span className="font-semibold text-gray-900">{to}</span> of{" "}
                            <span className="font-semibold text-gray-900">{total}</span> {noun}
                        </>
                    )}
                </span>
                <label className="flex items-center gap-2">
                    <span className="whitespace-nowrap">Per page</span>
                    <Select
                        value={pageSize}
                        onChange={(e) => setPageSize(Number(e.target.value))}
                        className="h-8 w-auto py-0 text-sm"
                        aria-label="Rows per page"
                    >
                        {PAGE_SIZES.map((n) => (
                            <option key={n} value={n}>
                                {n}
                            </option>
                        ))}
                    </Select>
                </label>
            </div>

            {pageCount > 1 && (
                <nav className="flex items-center gap-1" aria-label="Pagination">
                    <Button variant="outline" size="icon" className="size-8" disabled={page === 1} onClick={() => setPage(1)} title="First page">
                        <ChevronsLeft />
                    </Button>
                    <Button variant="outline" size="icon" className="size-8" disabled={page === 1} onClick={() => setPage(page - 1)} title="Previous page">
                        <ChevronLeft />
                    </Button>
                    {pageList(page, pageCount).map((p) =>
                        typeof p === "string" ? (
                            <span key={p} className="px-1 text-gray-400">
                                …
                            </span>
                        ) : (
                            <Button
                                key={p}
                                variant={p === page ? "default" : "outline"}
                                size="icon"
                                className={`size-8 ${p === page ? "bg-emerald-700 hover:bg-emerald-800" : ""}`}
                                onClick={() => setPage(p)}
                                aria-current={p === page ? "page" : undefined}
                            >
                                {p}
                            </Button>
                        )
                    )}
                    <Button variant="outline" size="icon" className="size-8" disabled={page === pageCount} onClick={() => setPage(page + 1)} title="Next page">
                        <ChevronRight />
                    </Button>
                    <Button variant="outline" size="icon" className="size-8" disabled={page === pageCount} onClick={() => setPage(pageCount)} title="Last page">
                        <ChevronsRight />
                    </Button>
                </nav>
            )}
        </div>
    );
}
