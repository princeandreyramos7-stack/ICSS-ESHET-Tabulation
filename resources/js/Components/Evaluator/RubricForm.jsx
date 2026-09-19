import { useEffect, useMemo, useState } from "react";
import { useForm } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, FileText } from "lucide-react";
import { toast } from "sonner";

import ConfirmDialog from "@/Components/ConfirmDialog";
import InputError from "@/Components/InputError";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Spinner } from "@/Components/ui/spinner";
import { Textarea } from "@/Components/ui/textarea";
import { fmtDateTime, fmtScore } from "@/lib/format";

/**
 * The digital version of the "Panel of Evaluator" rubric sheet for one paper.
 * Mount with key={paper.id} so the form resets when the paper changes.
 */
export default function RubricForm({ paper, track, criteria, onDirtyChange, onPrev, onNext, hasPrev, hasNext }) {
    const locked = track.is_locked;
    const evaluation = paper.evaluation;
    const alreadySubmitted = paper.submitted;

    const initialScores = useMemo(() => {
        const scores = {};
        criteria.forEach((c) => {
            const existing = evaluation?.scores?.[c.id];
            scores[c.id] = existing !== undefined && existing !== null ? String(existing) : "";
        });
        return scores;
    }, [criteria, evaluation]);

    const { data, setData, post, processing, errors, isDirty } = useForm({
        scores: initialScores,
        comments: evaluation?.comments ?? "",
    });

    useEffect(() => {
        onDirtyChange?.(isDirty && !processing);
    }, [isDirty, processing, onDirtyChange]);

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [clientErrors, setClientErrors] = useState({});
    // Status of the paper being opened, announced as a toast. A shared id means
    // stepping quickly through papers replaces the notice instead of stacking.
    useEffect(() => {
        if (locked) {
            toast.warning(`Track ${track.number} is locked. Ratings are read-only.`, {
                id: "paper-status",
                description: "Contact the administrator if a correction is needed.",
            });
        } else if (alreadySubmitted) {
            toast.info(`Paper ${paper.paper_no} already evaluated: ${fmtScore(paper.total)} / 100.`, {
                id: "paper-status",
                description: `Submitted ${fmtDateTime(paper.submitted_at)}. You may revise it until the track is locked.`,
            });
        } else {
            toast.dismiss("paper-status");
        }
    }, [paper.id, locked, alreadySubmitted]);

    const total = useMemo(
        () =>
            criteria.reduce((sum, c) => {
                const n = parseFloat(data.scores[c.id]);
                return sum + (Number.isFinite(n) ? n : 0);
            }, 0),
        [criteria, data.scores]
    );
    const maxTotal = useMemo(() => criteria.reduce((s, c) => s + c.weight, 0), [criteria]);

    /**
     * Hard input guard. Only digits with up to two decimals are accepted, and a
     * value above the criterion's weight is clamped to that weight (typing "21"
     * into a 20-point criterion yields "20" and a short notice).
     */
    const setScore = (criterionId, rawValue, weight) => {
        let value = rawValue;
        if (value !== "") {
            if (!/^\d*\.?\d{0,2}$/.test(value)) return; // ignore the keystroke
            if (Number(value) > weight) {
                value = String(weight);
                toast.warning(`Maximum for this criterion is ${weight}.`, { id: `max-${criterionId}` });
            }
        }
        setData("scores", { ...data.scores, [criterionId]: value });
        if (clientErrors[criterionId]) {
            setClientErrors((prev) => {
                const next = { ...prev };
                delete next[criterionId];
                return next;
            });
        }
    };

    /** Validates locally before showing the confirmation dialog. */
    const validate = () => {
        const found = {};
        criteria.forEach((c) => {
            const raw = data.scores[c.id];
            if (raw === "" || raw === null || raw === undefined) {
                found[c.id] = "A rating is required.";
                return;
            }
            const n = Number(raw);
            if (!Number.isFinite(n)) {
                found[c.id] = "Enter a valid number.";
            } else if (n < 0) {
                found[c.id] = "Rating cannot be negative.";
            } else if (n > c.weight) {
                found[c.id] = `Maximum for this criterion is ${c.weight}.`;
            } else if (!/^\d+(\.\d{1,2})?$/.test(String(raw).trim())) {
                found[c.id] = "Use at most two decimal places.";
            }
        });
        setClientErrors(found);
        const missing = Object.keys(found).length;
        if (missing > 0) {
            toast.error(
                missing === 1
                    ? "One criterion still needs a valid rating."
                    : `${missing} criteria still need a valid rating.`,
                { id: "rubric-validation" }
            );
        }
        return missing === 0;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (locked || processing) return;
        if (validate()) setConfirmOpen(true);
    };

    const confirmSubmit = () => {
        post(route("evaluator.papers.evaluate.store", paper.id), {
            preserveScroll: true,
            onError: (errs) => {
                const first = Object.values(errs)[0];
                toast.error(first || "Your ratings could not be saved. Please check the form.", {
                    id: "rubric-validation",
                });
            },
            onFinish: () => setConfirmOpen(false),
        });
    };

    const errorFor = (criterionId) => clientErrors[criterionId] || errors[`scores.${criterionId}`];

    return (
        <>
            <Card className="shadow-sm">
                <CardContent className="grid gap-2 p-4 sm:grid-cols-[auto_1fr] sm:gap-x-6 sm:p-5">
                    <span className="text-xs font-semibold uppercase text-gray-500 sm:pt-0.5">Paper No.</span>
                    <span className="text-lg font-bold text-gray-900">{paper.paper_no}</span>

                    <span className="text-xs font-semibold uppercase text-gray-500 sm:pt-0.5">Title</span>
                    <span className="text-base font-medium text-gray-900">{paper.title}</span>

                    <span className="text-xs font-semibold uppercase text-gray-500 sm:pt-0.5">Researcher</span>
                    <span className="text-base text-gray-900">
                        {paper.researcher}
                        {paper.affiliation && (
                            <span className="block text-sm text-gray-500">{paper.affiliation}</span>
                        )}
                    </span>

                    {paper.has_manuscript && (
                        <>
                            <span className="text-xs font-semibold uppercase text-gray-500 sm:pt-0.5">Manuscript</span>
                            <div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.open(paper.manuscript_url, '_blank')}
                                    className="gap-2"
                                >
                                    <FileText className="size-4" />
                                    View Manuscript PDF
                                </Button>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>

            <form onSubmit={handleSubmit} noValidate>
                <Card className="shadow-sm">
                    <CardHeader className="border-b p-4 sm:p-5">
                        <CardTitle className="text-base">Evaluation Criteria</CardTitle>
                        <p className="text-sm text-gray-500">
                            Rate each criterion from 0 up to its percentage weight. The total is out of{" "}
                            {maxTotal}.
                        </p>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="hidden grid-cols-[1fr_110px_150px] gap-4 border-b bg-gray-50 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 md:grid">
                            <span>Criteria and description</span>
                            <span className="text-center">Percentage</span>
                            <span className="text-center">Rating</span>
                        </div>

                        <ol className="divide-y">
                            {criteria.map((c, index) => {
                                const err = errorFor(c.id);
                                return (
                                    <li
                                        key={c.id}
                                        className="grid gap-3 p-4 sm:p-5 md:grid-cols-[1fr_110px_150px] md:items-start md:gap-4"
                                    >
                                        <div>
                                            <p className="font-semibold text-gray-900">
                                                {index + 1}. {c.name}
                                            </p>
                                            <p className="mt-1 text-sm leading-relaxed text-gray-600">
                                                {c.description}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2 md:justify-center">
                                            <span className="text-xs font-medium uppercase text-gray-500 md:hidden">
                                                Percentage
                                            </span>
                                            <Badge variant="warning" className="text-sm">
                                                {c.weight}%
                                            </Badge>
                                        </div>
                                        <div>
                                            <Label htmlFor={`score-${c.id}`} className="md:sr-only">
                                                Rating (0 to {c.weight})
                                            </Label>
                                            <div className="mt-1 flex items-center gap-2 md:mt-0">
                                                <Input
                                                    id={`score-${c.id}`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min="0"
                                                    max={c.weight}
                                                    step="0.01"
                                                    placeholder="0"
                                                    disabled={locked}
                                                    value={data.scores[c.id]}
                                                    onChange={(e) => setScore(c.id, e.target.value, c.weight)}
                                                    onKeyDown={(e) => {
                                                        // Block characters a numeric field would otherwise accept.
                                                        if (["e", "E", "+", "-"].includes(e.key)) e.preventDefault();
                                                    }}
                                                    onWheel={(e) => e.currentTarget.blur()}
                                                    aria-invalid={Boolean(err)}
                                                    className={`h-11 text-center text-lg font-semibold ${
                                                        err
                                                            ? "border-red-500 focus-visible:ring-red-500"
                                                            : ""
                                                    }`}
                                                />
                                                <span className="shrink-0 text-sm text-gray-500">/ {c.weight}</span>
                                            </div>
                                            <InputError message={err} className="mt-1" />
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>

                        <div className="flex items-center justify-between border-t bg-emerald-50 px-4 py-4 sm:px-5">
                            <span className="text-sm font-semibold uppercase tracking-wide text-emerald-900">
                                Total
                            </span>
                            <span className="text-2xl font-bold text-emerald-900">
                                {fmtScore(total)}{" "}
                                <span className="text-base font-medium text-emerald-700">/ {maxTotal}</span>
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card className="mt-4 shadow-sm">
                    <CardHeader className="p-4 pb-2 sm:p-5 sm:pb-2">
                        <CardTitle className="text-base">
                            Additional comments / suggestions about the study and presentation
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-4 pt-0 sm:p-5 sm:pt-0">
                        <Textarea
                            rows={4}
                            maxLength={3000}
                            disabled={locked}
                            value={data.comments}
                            onChange={(e) => setData("comments", e.target.value)}
                            placeholder="Optional"
                        />
                        <div className="mt-1 flex justify-between">
                            <InputError message={errors.comments} />
                            <span className="text-xs text-gray-400">{data.comments.length} / 3000</span>
                        </div>
                    </CardContent>
                </Card>

                {errors.scores && <p className="mt-3 text-sm text-red-600">{errors.scores}</p>}

                <div className="sticky bottom-0 mt-4 flex items-center justify-between gap-3 border-t bg-gray-50/95 py-3 backdrop-blur sm:static sm:border-0 sm:bg-transparent">
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" onClick={onPrev} disabled={!hasPrev || processing}>
                            <ChevronLeft />
                            <span className="hidden sm:inline">Previous</span>
                        </Button>
                        <Button type="button" variant="outline" onClick={onNext} disabled={!hasNext || processing}>
                            <span className="hidden sm:inline">Next</span>
                            <ChevronRight />
                        </Button>
                    </div>
                    {!locked && (
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-emerald-700 px-6 hover:bg-emerald-800"
                        >
                            {processing ? (
                                <>
                                    <Spinner /> Saving...
                                </>
                            ) : alreadySubmitted ? (
                                "Update Evaluation"
                            ) : (
                                "Submit Evaluation"
                            )}
                        </Button>
                    )}
                </div>
            </form>

            <ConfirmDialog
                open={confirmOpen}
                onOpenChange={(open) => !processing && setConfirmOpen(open)}
                title={alreadySubmitted ? "Update this evaluation?" : "Submit this evaluation?"}
                description={`Paper ${paper.paper_no} - ${paper.researcher}`}
                confirmLabel={alreadySubmitted ? "Yes, update" : "Yes, submit"}
                processing={processing}
                onConfirm={confirmSubmit}
            >
                <ul className="divide-y rounded-md border text-sm">
                    {criteria.map((c) => (
                        <li key={c.id} className="flex items-center justify-between gap-4 px-3 py-2">
                            <span className="text-gray-700">{c.name}</span>
                            <span className="shrink-0 font-semibold">
                                {fmtScore(data.scores[c.id])} / {c.weight}
                            </span>
                        </li>
                    ))}
                    <li className="flex items-center justify-between bg-emerald-50 px-3 py-2 font-bold text-emerald-900">
                        <span>Total</span>
                        <span>
                            {fmtScore(total)} / {maxTotal}
                        </span>
                    </li>
                </ul>
            </ConfirmDialog>
        </>
    );
}
