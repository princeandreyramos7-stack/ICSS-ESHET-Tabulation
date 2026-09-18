import { useCallback, useEffect, useMemo, useRef } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { toast } from "sonner";
import { ArrowLeftRight, ClipboardCheck, FileText, Lock } from "lucide-react";

import PaperNavigator from "@/Components/Evaluator/PaperNavigator";
import RubricForm from "@/Components/Evaluator/RubricForm";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import AppLayout from "@/Layouts/AppLayout";

/**
 * Single-page evaluator workspace:
 *   track tabs  ->  paper navigator  ->  rubric form.
 * Everything is loaded once; moving between tracks/papers is instant and
 * only updates the URL (?track=&paper=) so positions are shareable.
 */
export default function Workspace({ tracks, criteria, selected, totals }) {
    const dirtyRef = useRef(false);

    const track = useMemo(
        () => tracks.find((t) => t.id === selected.track_id) ?? tracks[0] ?? null,
        [tracks, selected.track_id]
    );
    const papers = track?.papers ?? [];
    const paperIndex = papers.findIndex((p) => p.id === selected.paper_id);
    const paper = paperIndex >= 0 ? papers[paperIndex] : papers[0] ?? null;

    // Warn before leaving the page with unsaved ratings.
    useEffect(() => {
        const handler = (e) => {
            if (dirtyRef.current) {
                e.preventDefault();
                e.returnValue = "";
            }
        };
        window.addEventListener("beforeunload", handler);
        return () => window.removeEventListener("beforeunload", handler);
    }, []);

    const onDirtyChange = useCallback((dirty) => {
        dirtyRef.current = dirty;
    }, []);

    /** Client-side move: updates props + URL without a server request. */
    const goTo = useCallback((trackId, paperId, force = false) => {
        if (dirtyRef.current && !force) {
            // Unsaved ratings: ask via an action toast instead of blocking the page.
            toast.warning("You have unsaved ratings on this paper.", {
                id: "unsaved-ratings",
                description: "Leave now and the ratings you typed will be lost.",
                duration: 10000,
                action: {
                    label: "Leave anyway",
                    onClick: () => goTo(trackId, paperId, true),
                },
                cancel: { label: "Stay" },
            });
            return;
        }
        dirtyRef.current = false;
        toast.dismiss("unsaved-ratings");
        router.replace({
            // Relative URL (third arg false): Inertia stores it verbatim as page.url,
            // which the sidebar compares against relative paths.
            url: route("evaluator.workspace", { track: trackId, paper: paperId ?? undefined }, false),
            props: (current) => ({ ...current, selected: { track_id: trackId, paper_id: paperId } }),
            preserveScroll: true,
            preserveState: true,
        });
    }, []);

    const selectPaper = (paperId) => goTo(track.id, paperId);
    const goPrev = () => paperIndex > 0 && selectPaper(papers[paperIndex - 1].id);
    const goNext = () => paperIndex < papers.length - 1 && selectPaper(papers[paperIndex + 1].id);

    const breadcrumbs = [
        { label: "Dashboard", href: route("evaluator.dashboard") },
        { label: "Evaluate", href: route("evaluator.workspace") },
        ...(track ? [{ label: `Track ${track.number}`, href: route("evaluator.workspace", { track: track.id }) }] : []),
        ...(paper ? [{ label: `Paper ${paper.paper_no}` }] : []),
    ];

    const remaining = totals.papers - totals.evaluated;

    return (
        <AppLayout
            breadcrumbs={breadcrumbs}
            actions={
                <div className="hidden items-center gap-2 text-sm text-gray-600 sm:flex">
                    <ClipboardCheck className="size-4 text-emerald-700" />
                    <span>
                        <strong className="text-gray-900">{totals.evaluated}</strong> / {totals.papers} evaluated
                    </span>
                    {remaining > 0 && <Badge variant="warning">{remaining} pending</Badge>}
                </div>
            }
        >
            <Head title={paper ? `Evaluate ${paper.paper_no}` : "Evaluate"} />

            <div className="mx-auto max-w-5xl space-y-4">

                {!track && (
                    <Card className="shadow-sm">
                        <CardContent className="p-6 text-center text-sm text-gray-600">
                            <p className="font-semibold text-gray-900">No track assigned yet</p>
                            <p className="mt-1">
                                The administrator assigns each evaluator to one parallel-session track. Please ask the
                                secretariat if you expected to see papers here.
                            </p>
                            <Button asChild variant="outline" size="sm" className="mt-4">
                                <Link href={route("evaluator.dashboard")}>Back to dashboard</Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {track && (
                    <Card className="shadow-sm">
                        <CardContent className="p-4 sm:p-5">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <p className="text-xs uppercase tracking-widest text-gray-500">
                                        Track {track.number}
                                        {track.venue ? ` · ${track.venue}` : ""}
                                    </p>
                                    <h2 className="text-base font-bold leading-snug text-emerald-900 sm:text-lg">
                                        {track.name}
                                    </h2>
                                </div>
                                <div className="flex items-center gap-2">
                                    {track.is_locked ? (
                                        <Badge variant="warning" className="gap-1">
                                            <Lock className="size-3" /> Locked
                                        </Badge>
                                    ) : (
                                        <Badge variant="success">
                                            {track.evaluated_count} of {track.papers_count} evaluated
                                        </Badge>
                                    )}
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route("evaluator.dashboard")}>
                                            <ArrowLeftRight />
                                            <span className="hidden sm:inline">Dashboard</span>
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            {papers.length > 0 ? (
                                <div className="mt-3 border-t pt-3">
                                    <PaperNavigator
                                        key={track.id}
                                        papers={papers}
                                        currentId={paper?.id}
                                        onSelect={selectPaper}
                                    />
                                </div>
                            ) : (
                                <p className="mt-4 flex items-center gap-2 rounded-md border border-dashed p-6 text-sm text-gray-500">
                                    <FileText className="size-4" />
                                    No papers have been added to this track yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {track && paper && (
                    <div className="space-y-4">
                        <RubricForm
                            key={paper.id}
                            paper={paper}
                            track={track}
                            criteria={criteria}
                            onDirtyChange={onDirtyChange}
                            onPrev={goPrev}
                            onNext={goNext}
                            hasPrev={paperIndex > 0}
                            hasNext={paperIndex < papers.length - 1}
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
