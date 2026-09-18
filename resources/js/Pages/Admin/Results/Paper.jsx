import { useRef } from "react";
import { Head, Link } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";

import PrintButton from "@/Components/PrintButton";
import PrintSheetHeader from "@/Components/PrintSheetHeader";
import { Button } from "@/Components/ui/button";
import AppLayout from "@/Layouts/AppLayout";
import { useFitToPage } from "@/hooks/use-fit-to-page";
import { fmtDateTime, fmtScore } from "@/lib/format";

/**
 * Per-paper breakdown: every evaluator's rating per criterion, their total,
 * and their written comments. Printable.
 */
export default function Paper({ result, track }) {
    const { paper, criteria, evaluations, criterion_averages, average, evaluations_count } = result;
    const sheetRef = useRef(null);
    useFitToPage(sheetRef);
    const maxTotal = criteria.reduce((s, c) => s + c.weight, 0);
    const withComments = evaluations.filter((e) => e.submitted && e.comments);

    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("admin.dashboard") },
                { label: "Results" },
                { label: `Track ${track.number}`, href: route("admin.results.track", track.id) },
                { label: `Paper ${paper.paper_no}` },
            ]}
            actions={
                <>
                    <Button asChild variant="ghost" size="sm" className="print:hidden">
                        <Link href={route("admin.results.track", track.id)}>
                            <ArrowLeft />
                            <span className="hidden sm:inline">Track results</span>
                        </Link>
                    </Button>
                    <PrintButton />
                </>
            }
        >
            <Head title={`Paper ${paper.paper_no}`} />

            <div className="mx-auto max-w-6xl">
                <div ref={sheetRef} className="print-sheet rounded-lg bg-white p-4 shadow-sm sm:p-8">
                    <PrintSheetHeader
                        trackLabel={`Track ${track.number}: ${track.name}`}
                        subtitle="Evaluation Breakdown"
                    />

                    <dl className="mb-5 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[auto_1fr]">
                        <dt className="font-semibold text-gray-500">Paper No.</dt>
                        <dd className="font-bold text-gray-900">{paper.paper_no}</dd>
                        <dt className="font-semibold text-gray-500">Title</dt>
                        <dd className="text-gray-900">{paper.title}</dd>
                        <dt className="font-semibold text-gray-500">Researcher</dt>
                        <dd className="text-gray-900">
                            {paper.researcher}
                            {paper.affiliation ? ` - ${paper.affiliation}` : ""}
                        </dd>
                    </dl>

                    <div className="overflow-x-auto rounded-md border border-gray-800">
                        <table className="w-full min-w-[720px] border-collapse text-sm">
                            <thead>
                                <tr className="bg-emerald-900 text-white">
                                    <th className="border border-gray-800 px-3 py-2 text-left font-semibold">
                                        Evaluator
                                    </th>
                                    {criteria.map((c, i) => (
                                        <th
                                            key={c.id}
                                            className="border border-gray-800 px-2 py-2 text-center font-semibold"
                                            title={c.name}
                                        >
                                            <span className="block">C{i + 1}</span>
                                            <span className="block text-[11px] font-normal text-emerald-200">
                                                {c.weight}%
                                            </span>
                                        </th>
                                    ))}
                                    <th className="border border-gray-800 bg-emerald-800 px-3 py-2 text-center font-semibold">
                                        Total / {maxTotal}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {evaluations.map((row) => (
                                    <tr key={row.evaluator_id} className="odd:bg-white even:bg-gray-50">
                                        <td className="border border-gray-800 px-3 py-2">
                                            <p className="font-medium text-gray-900">{row.evaluator_name}</p>
                                            <p className="text-[11px] text-gray-500">
                                                {row.submitted
                                                    ? `Submitted ${fmtDateTime(row.submitted_at)}`
                                                    : "Not yet submitted"}
                                            </p>
                                        </td>
                                        {criteria.map((c) => (
                                            <td
                                                key={c.id}
                                                className="border border-gray-800 px-2 py-2 text-center tabular-nums"
                                            >
                                                {fmtScore(row.scores[c.id])}
                                            </td>
                                        ))}
                                        <td className="border border-gray-800 bg-emerald-50 px-3 py-2 text-center font-bold tabular-nums text-emerald-900">
                                            {fmtScore(row.total)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="bg-amber-50 font-bold">
                                    <td className="border border-gray-800 px-3 py-2 text-gray-900">
                                        Average
                                        <span className="block text-[11px] font-normal text-gray-500">
                                            {evaluations_count} of {evaluations.length} submitted
                                        </span>
                                    </td>
                                    {criteria.map((c) => (
                                        <td
                                            key={c.id}
                                            className="border border-gray-800 px-2 py-2 text-center tabular-nums"
                                        >
                                            {fmtScore(criterion_averages[c.id])}
                                        </td>
                                    ))}
                                    <td className="border border-gray-800 px-3 py-2 text-center text-base tabular-nums text-emerald-900">
                                        {fmtScore(average)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <ol className="mt-3 grid gap-x-6 gap-y-1 text-xs text-gray-600 sm:grid-cols-2">
                        {criteria.map((c, i) => (
                            <li key={c.id}>
                                <span className="font-semibold text-gray-800">C{i + 1}</span> - {c.name} ({c.weight}%)
                            </li>
                        ))}
                    </ol>

                    <div className="mt-6">
                        <h3 className="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-700">
                            Comments and suggestions
                        </h3>
                        {withComments.length === 0 ? (
                            <p className="text-sm text-gray-500">No comments were submitted.</p>
                        ) : (
                            <ul className="space-y-3">
                                {withComments.map((row) => (
                                    <li
                                        key={row.evaluator_id}
                                        className="rounded-md border border-gray-200 bg-gray-50 p-3 print:break-inside-avoid"
                                    >
                                        <p className="text-xs font-semibold text-gray-700">{row.evaluator_name}</p>
                                        <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800">
                                            {row.comments}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
