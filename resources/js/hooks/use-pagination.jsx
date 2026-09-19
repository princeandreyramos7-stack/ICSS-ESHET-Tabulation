import { useEffect, useMemo, useState } from "react";

export const PAGE_SIZES = [10, 25, 50, 100];

/**
 * Client-side pagination over an already-filtered list.
 * The page snaps back to 1 whenever the list changes (search, filter, add, delete),
 * and is clamped so a delete on the last page never leaves an empty screen.
 */
export function usePagination(items, initialPageSize = 25) {
    const [page, setPage] = useState(1);
    const [pageSize, setPageSize] = useState(initialPageSize);

    const total = items.length;
    const pageCount = Math.max(1, Math.ceil(total / pageSize));

    useEffect(() => {
        setPage(1);
    }, [total, pageSize]);

    const current = Math.min(page, pageCount);
    const start = (current - 1) * pageSize;

    const pageItems = useMemo(() => items.slice(start, start + pageSize), [items, start, pageSize]);

    return {
        page: current,
        setPage: (p) => setPage(Math.min(Math.max(1, p), pageCount)),
        pageSize,
        setPageSize,
        pageCount,
        total,
        from: total === 0 ? 0 : start + 1,
        to: Math.min(start + pageSize, total),
        offset: start,
        pageItems,
    };
}
