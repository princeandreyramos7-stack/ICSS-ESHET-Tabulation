import { useEffect, useMemo, useRef, useState } from "react";
import { Check, LayoutGrid, List, Search, X } from "lucide-react";

import { Input } from "@/Components/ui/input";
import { fmtScore } from "@/lib/format";

/**
 * Navigator for the papers in one track. Scales from 2 to 100+ papers:
 *  - Grid view: numbered chips that wrap (no horizontal scrolling), like an
 *    exam question map. Evaluated = green with a check, current = amber.
 *  - List view: compact rows with paper no., title, researcher and status,
 *    for finding a specific researcher quickly.
 * Both views share a search box and a Pending / Evaluated filter.
 */
export default function PaperNavigator({ papers, currentId, onSelect }) {
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("all"); // all | pending | done
    const [view, setView] = useState(() => (papers.length > 12 ? "list" : "grid"));
    const currentRowRef = useRef(null);

    const currentIndex = papers.findIndex((p) => p.id === currentId);
    const pendingCount = papers.filter((p) => !p.submitted).length;

    const visible = useMemo(() => {
        const q = query.trim().toLowerCase();
        return papers.filter((p) => {
            if (status === "pending" && p.submitted) return false;
            if (status === "done" && !p.submitted) return false;
            if (!q) return true;
            return (
                p.paper_no.toLowerCase().includes(q) ||
                p.title.toLowerCase().includes(q) ||
                p.researcher.toLowerCase().includes(q)
            );
        });
    }, [papers, query, status]);

    // Keep the current row in view inside the scrollable list.
    useEffect(() => {
        if (view === "list") {
            currentRowRef.current?.scrollIntoView({ block: "nearest" });
        }
    }, [currentId, view]);

    const filterButton = (value, label, count) => (
        <button
            type="button"
            onClick={() => setStatus(value)}
            aria-pressed={status === value}
            className={`rounded-md px-2.5 py-1 text-xs font-medium transition ${
                status === value
                    ? "bg-emerald-800 text-white"
                    : "text-gray-600 hover:bg-gray-100"
            }`}
        >
            {label}
            <span className={`ml-1 tabular-nums ${status === value ? "text-emerald-200" : "text-gray-400"}`}>
                {count}
            </span>
        </button>
    );

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <p className="mr-auto text-sm text-gray-700">
                    Paper{" "}
                    <strong className="text-gray-900">{currentIndex >= 0 ? currentIndex + 1 : "-"}</strong> of{" "}
                    <strong className="text-gray-900">{papers.length}</strong>
                    {pendingCount > 0 ? (
                        <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                            {pendingCount} pending
                        </span>
                    ) : (
                        <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                            All evaluated
                        </span>
                    )}
                </p>

                <div className="relative w-full sm:w-56">
                    <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Find paper no. or researcher"
                        className="h-8 bg-white pl-8 pr-8 text-sm"
                        aria-label="Search papers in this track"
                    />
                    {query && (
                        <button
                            type="button"
                            onClick={() => setQuery("")}
                            className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            aria-label="Clear search"
                        >
                            <X className="size-4" />
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-0.5 rounded-md border bg-white p-0.5">
                    {filterButton("all", "All", papers.length)}
                    {filterButton("pending", "Pending", pendingCount)}
                    {filterButton("done", "Evaluated", papers.length - pendingCount)}
                </div>

                <div className="flex items-center gap-0.5 rounded-md border bg-white p-0.5">
                    <button
                        type="button"
                        onClick={() => setView("grid")}
                        aria-pressed={view === "grid"}
                        title="Grid view"
                        className={`rounded p-1.5 ${view === "grid" ? "bg-emerald-800 text-white" : "text-gray-500 hover:bg-gray-100"}`}
                    >
                        <LayoutGrid className="size-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => setView("list")}
                        aria-pressed={view === "list"}
                        title="List view"
                        className={`rounded p-1.5 ${view === "list" ? "bg-emerald-800 text-white" : "text-gray-500 hover:bg-gray-100"}`}
                    >
                        <List className="size-4" />
                    </button>
                </div>
            </div>

            {visible.length === 0 ? (
                <p className="rounded-md border border-dashed p-4 text-center text-sm text-gray-500">
                    No papers match your search.
                </p>
            ) : view === "grid" ? (
                <ol className="flex flex-wrap gap-1.5" aria-label="Papers in this track">
                    {visible.map((paper) => {
                        const number = papers.indexOf(paper) + 1;
                        const current = paper.id === currentId;
                        return (
                            <li key={paper.id}>
                                <button
                                    type="button"
                                    onClick={() => onSelect(paper.id)}
                                    aria-current={current ? "step" : undefined}
                                    title={`${paper.paper_no} - ${paper.title} (${paper.researcher})`}
                                    className={`relative flex size-10 items-center justify-center rounded-md border text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 ${
                                        current
                                            ? "border-amber-500 bg-amber-400 text-emerald-950 shadow"
                                            : paper.submitted
                                            ? "border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700"
                                            : "border-gray-300 bg-white text-gray-700 hover:border-emerald-500 hover:bg-emerald-50"
                                    }`}
                                >
                                    {number}
                                    {paper.submitted && (
                                        <Check
                                            className={`absolute -right-1 -top-1 size-3.5 rounded-full p-0.5 ${
                                                current ? "bg-emerald-700 text-white" : "bg-white text-emerald-700"
                                            }`}
                                            aria-label="Evaluated"
                                        />
                                    )}
                                </button>
                            </li>
                        );
                    })}
                </ol>
            ) : (
                <ol className="max-h-72 divide-y overflow-y-auto rounded-md border bg-white" aria-label="Papers in this track">
                    {visible.map((paper) => {
                        const number = papers.indexOf(paper) + 1;
                        const current = paper.id === currentId;
                        return (
                            <li key={paper.id} ref={current ? currentRowRef : null}>
                                <button
                                    type="button"
                                    onClick={() => onSelect(paper.id)}
                                    aria-current={current ? "step" : undefined}
                                    className={`flex w-full items-center gap-3 px-3 py-2 text-left text-sm transition ${
                                        current ? "bg-amber-50" : "hover:bg-gray-50"
                                    }`}
                                >
                                    <span
                                        className={`flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                            current
                                                ? "bg-amber-400 text-emerald-950"
                                                : paper.submitted
                                                ? "bg-emerald-600 text-white"
                                                : "bg-gray-200 text-gray-700"
                                        }`}
                                    >
                                        {paper.submitted && !current ? <Check className="size-3.5" /> : number}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-baseline gap-2">
                                            <span className="shrink-0 font-bold text-gray-900">{paper.paper_no}</span>
                                            <span className="truncate text-gray-800">{paper.title}</span>
                                        </span>
                                        <span className="block truncate text-xs text-gray-500">{paper.researcher}</span>
                                    </span>
                                    <span className="shrink-0 text-right">
                                        {paper.submitted ? (
                                            <>
                                                <span className="block text-xs font-semibold text-emerald-700">Evaluated</span>
                                                <span className="block text-xs tabular-nums text-gray-500">
                                                    {fmtScore(paper.total)} / 100
                                                </span>
                                            </>
                                        ) : (
                                            <span className="text-xs font-medium text-amber-700">Pending</span>
                                        )}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
            )}
        </div>
    );
}
