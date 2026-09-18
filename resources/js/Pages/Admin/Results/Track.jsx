import { useEffect, useRef } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { toast } from "sonner";
import { Eye, Lock } from "lucide-react";

import EvaluatorSignatures from "@/Components/EvaluatorSignatures";
import PrintButton from "@/Components/PrintButton";
import PrintSheetHeader from "@/Components/PrintSheetHeader";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import AppLayout from "@/Layouts/AppLayout";
import { useFitToPage } from "@/hooks/use-fit-to-page";
import { fmtScore, ordinal } from "@/lib/format";

/**
 * Printable result sheet for one track.
 * Rows = papers, columns = each evaluator's total, then average and rank.
 */
export default function Track({ result }) {
    const { track, evaluators, papers } = result;
    const topAverage = papers.find((p) => p.rank === 1)?.average ?? null;
    const sheetRef = useRef(null);
    useFitToPage(sheetRef);

    useEffect(() => {
        if (!track.is_locked) {
            toast.warning(`Track ${track.number} is still open.`, {
                id: "track-open",
                description: "Evaluators can still change ratings. Lock the track before printing the final sheet.",
                duration: 8000,
                action: { label: "Tracks & Locks", onClick: () => router.visit(route("admin.tracks.index")) },
            });
        } else {
            toast.dismiss("track-open");
        }
    }, [track.id, track.is_locked]);

    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("admin.dashboard") },
                { label: "Results" },
                { label: `Track ${track.number}` },
            ]}
            actions={<PrintButton label="Print sheet" />}
        >
            <Head title={`Results Track ${track.number}`} />

            <div className="mx-auto max-w-7xl">

                <div ref={sheetRef} className="print-sheet rounded-lg bg-white p-4 shadow-sm sm:p-8">
                    <PrintSheetHeader
                        trackLabel={`Track ${track.number}: ${track.name}`}
                        subtitle="Summary of Evaluation Scores"
                        venue={track.venue}
                    />

                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                        <span>
                            {papers.length} paper{papers.length === 1 ? "" : "s"} - {evaluators.length}{" "}
                            evaluator{evaluators.length === 1 ? "" : "s"}
                        </span>
                        {track.is_locked && (
                            <Badge variant="warning" className="gap-1">
                                <Lock className="size-3" /> Final (locked)
                            </Badge>
                        )}
                    </div>

                    {evaluators.length === 0 ? (
                        <p className="rounded-md border border-dashed p-8 text-center text-gray-500">
                            No evaluators are assigned to this track yet. Assign the panel from the Evaluators page.
                        </p>
                    ) : papers.length === 0 ? (
                        <p className="rounded-md border border-dashed p-8 text-center text-gray-500">
                            No papers have been added to this track.
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-md border border-gray-800">
                            <table className="w-full min-w-[720px] border-collapse text-sm">
                                <thead>
                                    <tr className="bg-emerald-900 text-white">
                                        <th className="border border-gray-800 px-3 py-2 text-left font-semibold">
                                            Paper No.
                                        </th>
                                        <th className="border border-gray-800 px-3 py-2 text-left font-semibold">
                                            Title / Researcher
                                        </th>
                                        {evaluators.map((e, i) => (
                                            <th
                                                key={e.id}
                                                className="border border-gray-800 px-2 py-2 text-center font-semibold"
                                                title={e.name}
                                            >
                                                <span className="block text-xs font-normal text-emerald-200">
                                                    Evaluator {i + 1}
                                                </span>
                                                <span className="block max-w-[120px] truncate">{e.name}</span>
                                            </th>
                                        ))}
                                        <th className="border border-gray-800 bg-emerald-800 px-3 py-2 text-center font-semibold">
                                            Average
                                        </th>
                                        <th className="border border-gray-800 bg-amber-400 px-3 py-2 text-center font-semibold text-emerald-950">
                                            Rank
                                        </th>
                                        <th className="border border-gray-800 px-2 py-2 print:hidden" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {papers.map((paper) => {
                                        const isTop = paper.rank === 1 && topAverage !== null;
                                        return (
                                            <tr
                                                key={paper.id}
                                                className={`${isTop ? "bg-amber-50" : "odd:bg-white even:bg-gray-50"} print:break-inside-avoid`}
                                            >
                                                <td className="border border-gray-800 px-3 py-2 font-bold text-gray-900">
                                                    {paper.paper_no}
                                                </td>
                                                <td className="border border-gray-800 px-3 py-2">
                                                    <p className="font-medium leading-snug text-gray-900">
                                                        {paper.title}
                                                    </p>
                                                    <p className="text-xs text-gray-600">{paper.researcher}</p>
                                                </td>
                                                {evaluators.map((e) => (
                                                    <td
                                                        key={e.id}
                                                        className="border border-gray-800 px-2 py-2 text-center tabular-nums"
                                                    >
                                                        {fmtScore(paper.totals[e.id])}
                                                    </td>
                                                ))}
                                                <td className="border border-gray-800 bg-emerald-50 px-3 py-2 text-center font-bold tabular-nums text-emerald-900">
                                                    {fmtScore(paper.average)}
                                                    {paper.evaluations_count > 0 &&
                                                        paper.evaluations_count < evaluators.length && (
                                                            <span
                                                                className="block text-[10px] font-normal text-amber-700"
                                                                title="Not all evaluators have submitted"
                                                            >
                                                                {paper.evaluations_count}/{evaluators.length} submitted
                                                            </span>
                                                        )}
                                                </td>
                                                <td className="border border-gray-800 px-3 py-2 text-center text-base font-bold text-gray-900">
                                                    {paper.rank ? ordinal(paper.rank) : "-"}
                                                </td>
                                                <td className="border border-gray-800 px-2 py-2 text-center print:hidden">
                                                    <Button asChild variant="ghost" size="sm" title="View breakdown">
                                                        <Link href={route("admin.results.paper", paper.id)}>
                                                            <Eye />
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <p className="mt-3 text-xs text-gray-500">
                        Each evaluator's score is the sum of five criteria (Originality 25, Significance 25,
                        Clarity of Presentation 15, Mastery of Subject 20, Presentation Materials 15) out of
                        100. Average is across evaluators who submitted. Rank uses competition ranking: tied
                        papers share a rank.
                    </p>

                    <EvaluatorSignatures
                        evaluators={evaluators}
                        chairs={[
                            { title: "Session Chair", name: track.session_chair },
                            { title: "Co-Session Chair", name: track.co_session_chair },
                        ]}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
