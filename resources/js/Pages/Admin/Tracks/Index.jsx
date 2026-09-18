import { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { Lock, LockOpen } from "lucide-react";

import ConfirmDialog from "@/Components/ConfirmDialog";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";
import AppLayout from "@/Layouts/AppLayout";

/**
 * Lock or unlock each track. Locking freezes evaluator submissions,
 * which is the signal that a track's results are final.
 */
export default function Index({ progress }) {
    const [pending, setPending] = useState(null); // { track, nextState }
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        if (!pending) return;
        setProcessing(true);
        router.patch(
            route("admin.tracks.lock", pending.track.id),
            { is_locked: pending.nextState },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setPending(null);
                },
            }
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { label: "Dashboard", href: route("admin.dashboard") },
                { label: "Tracks & Locks" },
            ]}
        >
            <Head title="Tracks" />

            <div className="mx-auto max-w-5xl space-y-4">
                <p className="text-sm text-gray-500">
                    Evaluators can submit and revise ratings while a track is <strong>open</strong>.
                    Lock a track once its presentations are finished to freeze the results. You can
                    unlock it again if a correction is needed.
                </p>

                <ul className="stagger space-y-3">
                    {progress.tracks.map((track) => (
                        <li key={track.id}>
                            <Card className="shadow-sm">
                                <CardContent className="grid gap-4 p-4 sm:p-5 md:grid-cols-[1fr_200px_auto] md:items-center">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold text-gray-900">
                                                Track {track.number}: {track.name}
                                            </p>
                                            {track.is_locked ? (
                                                <Badge variant="warning" className="gap-1">
                                                    <Lock className="size-3" /> Locked
                                                </Badge>
                                            ) : (
                                                <Badge variant="success">Open</Badge>
                                            )}
                                        </div>
                                        <p className="text-sm text-gray-500">
                                            {track.papers_count} paper{track.papers_count === 1 ? "" : "s"} &middot;{" "}
                                            {track.evaluators_count} evaluator{track.evaluators_count === 1 ? "" : "s"} on the panel
                                            {track.venue ? ` · ${track.venue}` : ""}
                                        </p>
                                        {track.evaluators_count === 0 && track.papers_count > 0 && (
                                            <p className="mt-1 text-xs font-medium text-amber-700">
                                                No evaluators assigned to this track yet.
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <div className="mb-1 flex justify-between text-xs text-gray-600">
                                            <span>
                                                {track.submitted} / {track.expected} evaluations
                                            </span>
                                            <span className="font-semibold">{track.percent}%</span>
                                        </div>
                                        <Progress value={track.percent} />
                                    </div>
                                    <Button
                                        type="button"
                                        variant={track.is_locked ? "outline" : "default"}
                                        className={track.is_locked ? "" : "bg-amber-500 text-emerald-950 hover:bg-amber-400"}
                                        onClick={() => setPending({ track, nextState: !track.is_locked })}
                                    >
                                        {track.is_locked ? (
                                            <>
                                                <LockOpen /> Unlock
                                            </>
                                        ) : (
                                            <>
                                                <Lock /> Lock track
                                            </>
                                        )}
                                    </Button>
                                </CardContent>
                            </Card>
                        </li>
                    ))}
                </ul>
            </div>

            <ConfirmDialog
                open={Boolean(pending)}
                onOpenChange={(open) => !open && !processing && setPending(null)}
                title={pending?.nextState ? "Lock this track?" : "Unlock this track?"}
                description={
                    pending?.nextState
                        ? `Evaluators will no longer be able to submit or change ratings for Track ${pending?.track.number}. ${
                              pending && pending.track.submitted < pending.track.expected
                                  ? `Note: only ${pending.track.submitted} of ${pending.track.expected} expected evaluations have been submitted.`
                                  : ""
                          }`
                        : `Evaluators will be able to submit and revise ratings for Track ${pending?.track.number} again.`
                }
                confirmLabel={pending?.nextState ? "Lock track" : "Unlock track"}
                destructive={Boolean(pending?.nextState)}
                processing={processing}
                onConfirm={confirm}
            />
        </AppLayout>
    );
}
