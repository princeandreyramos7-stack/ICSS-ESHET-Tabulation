import { useRef } from "react";
import { Head, Link } from "@inertiajs/react";
import { Download, Lock, Trophy } from "lucide-react";

import EvaluatorSignatures from "@/Components/EvaluatorSignatures";
import PrintButton from "@/Components/PrintButton";
import PrintSheetHeader from "@/Components/PrintSheetHeader";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import AppLayout from "@/Layouts/AppLayout";
import { useFitToPage } from "@/hooks/use-fit-to-page";
import { fmtScore, ordinal } from "@/lib/format";

/**
 * Overall results: every track's ranking side by side, plus a cross-track
 * leaderboard. Printable on one page.
 */
export default function Overall({ result }) {
    const { tracks, leaderboard, evaluators, all_locked: allLocked } = result;
    const sheetRef = useRef(null);
    useFitToPage(sheetRef);

    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("admin.dashboard") },
                { label: "Results" },
                { label: "Overall" },
            ]}
            actions={
                <>
                    <Button asChild variant="default" size="sm">
                        <a href={route('admin.results.overall.pdf')}>
                            <Download />
                            Download PDF
                        </a>
                    </Button>
                    <PrintButton label="Print sheet" />
                </>
            }
        >
            <Head title="Overall Results" />

            <div className="mx-auto max-w-7xl">
                <div ref={sheetRef} className="print-sheet rounded-lg bg-white p-4 shadow-sm sm:p-8">
                    <PrintSheetHeader subtitle="Overall Results - All Tracks" />

                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                        <span>
                            {tracks.length} tracks &middot; {evaluators.length} evaluator{evaluators.length === 1 ? "" : "s"}
                        </span>
                        {allLocked ? (
                            <Badge variant="warning" className="gap-1">
                                <Lock className="size-3" /> Final (all tracks locked)
                            </Badge>
                        ) : (
                            <Badge variant="secondary" className="print:hidden">
                                Provisional: some tracks are still open
                            </Badge>
                        )}
                    </div>

                    {/* Leaderboard */}
                    <section className="mb-5">
                        <h3 className="mb-2 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-emerald-900">
                            <Trophy className="size-4 text-amber-500" /> Top papers across all tracks
                        </h3>
                        {leaderboard.length === 0 ? (
                            <p className="rounded-md border border-dashed p-4 text-center text-sm text-gray-500">
                                No submitted evaluations yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto rounded-md border border-gray-800">
                                <table className="w-full min-w-[640px] border-collapse text-sm">
                                    <thead>
                                        <tr className="bg-emerald-900 text-white">
                                            <th className="border border-gray-800 px-2 py-1.5 text-center font-semibold">Overall</th>
                                            <th className="border border-gray-800 px-2 py-1.5 text-left font-semibold">Track</th>
                                            <th className="border border-gray-800 px-2 py-1.5 text-left font-semibold">Paper No.</th>
                                            <th className="border border-gray-800 px-2 py-1.5 text-left font-semibold">Title / Researcher</th>
                                            <th className="border border-gray-800 px-2 py-1.5 text-center font-semibold">Track rank</th>
                                            <th className="border border-gray-800 bg-amber-400 px-2 py-1.5 text-center font-semibold text-emerald-950">
                                                Average
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {leaderboard.map((p) => (
                                            <tr key={p.id} className={p.overall_rank === 1 ? "bg-amber-50" : "odd:bg-white even:bg-gray-50"}>
                                                <td className="border border-gray-800 px-2 py-1.5 text-center font-bold">{ordinal(p.overall_rank)}</td>
                                                <td className="border border-gray-800 px-2 py-1.5 text-xs">T{p.track_number}</td>
                                                <td className="border border-gray-800 px-2 py-1.5 font-bold">
                                                    <Link href={route("admin.results.paper", p.id)} className="hover:underline">
                                                        {p.paper_no}
                                                    </Link>
                                                </td>
                                                <td className="border border-gray-800 px-2 py-1.5">
                                                    <p className="leading-snug text-gray-900">{p.title}</p>
                                                    <p className="text-xs text-gray-600">{p.researcher}</p>
                                                </td>
                                                <td className="border border-gray-800 px-2 py-1.5 text-center">{ordinal(p.rank)}</td>
                                                <td className="border border-gray-800 bg-emerald-50 px-2 py-1.5 text-center font-bold tabular-nums text-emerald-900">
                                                    {fmtScore(p.average)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    {/* Per-track rankings */}
                    <section className="grid gap-4 md:grid-cols-2 print:grid-cols-3 print:gap-3">
                        {tracks.map(({ track, papers }) => (
                            <div key={track.id} className="break-inside-avoid rounded-md border border-gray-800">
                                <div className="flex items-start justify-between gap-2 border-b border-gray-800 bg-emerald-900 px-3 py-1.5 text-white">
                                    <div className="min-w-0">
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-amber-300">
                                            Track {track.number}
                                        </p>
                                        <p className="truncate text-xs font-semibold" title={track.name}>
                                            {track.name}
                                        </p>
                                    </div>
                                    {track.is_locked ? (
                                        <Lock className="mt-1 size-3.5 shrink-0 text-amber-300" title="Locked" />
                                    ) : (
                                        <Link
                                            href={route("admin.results.track", track.id)}
                                            className="shrink-0 text-[10px] text-emerald-200 hover:underline print:hidden"
                                        >
                                            Full sheet
                                        </Link>
                                    )}
                                </div>
                                {papers.length === 0 ? (
                                    <p className="p-3 text-center text-xs text-gray-500">No papers</p>
                                ) : (
                                    <table className="w-full border-collapse text-xs">
                                        <thead>
                                            <tr className="bg-gray-100 text-gray-700">
                                                <th className="border-b border-gray-300 px-2 py-1 text-center font-semibold">Rank</th>
                                                <th className="border-b border-gray-300 px-2 py-1 text-left font-semibold">Paper</th>
                                                <th className="border-b border-gray-300 px-2 py-1 text-left font-semibold">Researcher</th>
                                                <th className="border-b border-gray-300 px-2 py-1 text-right font-semibold">Avg</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {papers.map((p) => (
                                                <tr key={p.id} className={p.rank === 1 ? "bg-amber-50 font-semibold" : ""}>
                                                    <td className="border-b border-gray-200 px-2 py-1 text-center tabular-nums">
                                                        {p.rank ? ordinal(p.rank) : "-"}
                                                    </td>
                                                    <td className="border-b border-gray-200 px-2 py-1">
                                                        <span className="font-semibold">{p.paper_no}</span>{" "}
                                                        <span className="text-gray-600">{p.title}</span>
                                                    </td>
                                                    <td className="border-b border-gray-200 px-2 py-1 text-gray-700">{p.researcher}</td>
                                                    <td className="border-b border-gray-200 px-2 py-1 text-right tabular-nums">
                                                        {fmtScore(p.average)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        ))}
                    </section>

                    <p className="mt-3 text-xs text-gray-500">
                        Average is the mean of submitted evaluator totals (out of 100). Track rank uses competition
                        ranking within the track; the overall list orders all papers by average regardless of track.
                    </p>

                    <EvaluatorSignatures evaluators={evaluators} />
                </div>
            </div>
        </AppLayout>
    );
}
