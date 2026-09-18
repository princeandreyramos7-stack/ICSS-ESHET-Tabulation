import { useEffect, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Activity,
    ArrowRight,
    Award,
    BarChart3,
    ClipboardCheck,
    PieChart,
    Target,
    Trophy,
    FileText,
    Lock,
    Printer,
    RefreshCw,
    Users,
} from "lucide-react";
import { toast } from "sonner";

import { Donut, ProgressBars, RankedBars, SimpleBars, STATUS_COLORS, TrendArea } from "@/Components/Charts";
import StatCard from "@/Components/StatCard";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";
import { Spinner } from "@/Components/ui/spinner";
import AppLayout from "@/Layouts/AppLayout";
import { fmtDateTime, fmtScore } from "@/lib/format";

function Panel({ title, subtitle, icon: Icon, action, children, className = "" }) {
    return (
        <Card className={`shadow-sm ${className}`}>
            <CardContent className="p-0">
                <div className="flex items-start justify-between gap-3 border-b px-5 py-4">
                    <div className="flex items-start gap-3">
                        {Icon && (
                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-800">
                                <Icon className="size-4" />
                            </span>
                        )}
                        <div>
                            <h3 className="font-semibold text-gray-900">{title}</h3>
                            {subtitle && <p className="text-xs text-gray-500">{subtitle}</p>}
                        </div>
                    </div>
                    {action}
                </div>
                <div className="p-5">{children}</div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ analytics }) {
    const { conference } = usePage().props;
    const {
        progress,
        timeline,
        average_by_track: avgByTrack,
        distribution,
        evaluator_activity: evaluators,
        leaders,
        recent,
        overall_average: overallAverage,
        criterion_averages: criterionAverages,
        top_papers: topPapers,
        track_status: trackStatus,
    } = analytics;

    const [refreshing, setRefreshing] = useState(false);
    const refresh = () => {
        if (refreshing) return;
        setRefreshing(true);
        router.reload({ only: ["analytics"], onFinish: () => setRefreshing(false) });
    };

    const overall = progress.expected ? Math.round((progress.submitted / progress.expected) * 100) : 0;
    const lockedCount = progress.tracks.filter((t) => t.is_locked).length;

    useEffect(() => {
        if (progress.evaluators === 0) {
            toast.warning("No evaluator accounts exist yet.", {
                id: "no-evaluators",
                description: "Add evaluators so the panel can sign in.",
                duration: 8000,
                action: { label: "Add evaluators", onClick: () => router.visit(route("admin.evaluators.index")) },
            });
        } else if (progress.papers === 0) {
            toast.warning("No papers have been added yet.", {
                id: "no-papers",
                description: "Add the research papers for each track.",
                duration: 8000,
                action: { label: "Add papers", onClick: () => router.visit(route("admin.papers.index")) },
            });
        }
    }, [progress.evaluators, progress.papers]);

    // Auto-refresh every 60s while the tab is visible, so the tabulation follows the event live.
    useEffect(() => {
        const id = setInterval(() => {
            if (document.visibilityState === "visible") {
                router.reload({ only: ["analytics"] });
            }
        }, 60000);
        return () => clearInterval(id);
    }, []);

    const trackBars = progress.tracks.map((t) => ({
        label: `T${t.number}`,
        name: t.name,
        submitted: t.submitted,
        expected: t.expected,
    }));

    const submissionSplit = [
        { name: "Submitted", value: progress.submitted },
        { name: "Pending", value: Math.max(0, progress.expected - progress.submitted) },
    ];
    const papersPerTrack = progress.tracks.map((t) => ({
        name: `Track ${t.number}`,
        title: `Track ${t.number}: ${t.name}`,
        value: t.papers_count,
    }));
    const topPaperRows = topPapers.map((p) => ({ ...p, label: `${p.paper_no} (${p.track})` }));
    const evaluatorTendency = evaluators
        .filter((e) => e.average !== null)
        .map((e) => ({ label: e.name, average: e.average }))
        .sort((a, b) => b.average - a.average);

    const bestTrackIndex = avgByTrack.reduce(
        (best, t, i) => (t.average !== null && (best < 0 || t.average > avgByTrack[best].average) ? i : best),
        -1
    );

    return (
        <AppLayout
            breadcrumbs={[{ label: "Dashboard" }]}
            actions={
                <Button variant="ghost" size="sm" onClick={refresh} disabled={refreshing} title="Refresh">
                    {refreshing ? <Spinner /> : <RefreshCw />}
                    <span className="hidden sm:inline">{refreshing ? "Refreshing..." : "Refresh"}</span>
                </Button>
            }
        >
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6">
                {/* Banner */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-700 p-5 text-white shadow sm:p-6">
                    <div aria-hidden="true" className="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full border border-amber-300/20" />
                    <div className="relative grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-300">
                                Administrator &middot; Live tabulation
                            </p>
                            <h2 className="mt-1 text-xl font-extrabold tracking-tight sm:text-2xl">{conference?.name}</h2>
                            <p className="mt-1 text-sm text-emerald-100">
                                {conference?.theme} &middot; {conference?.dates} &middot; {conference?.venue}
                            </p>
                        </div>
                        <div className="w-full lg:w-80">
                            <div className="mb-1 flex justify-between text-xs text-emerald-100">
                                <span>Evaluations submitted</span>
                                <span className="font-semibold text-white">
                                    {progress.submitted} / {progress.expected} ({overall}%)
                                </span>
                            </div>
                            <Progress value={overall} className="bg-emerald-950/50" barClassName="bg-amber-400" />
                        </div>
                    </div>
                </div>

                {/* KPIs */}
                <div className="stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <StatCard label="Papers" value={progress.papers} icon={FileText} tone="blue" />
                    <StatCard label="Evaluators" value={progress.evaluators} icon={Users} tone="gray" />
                    <StatCard
                        label="Submitted"
                        value={progress.submitted}
                        hint={`of ${progress.expected} expected`}
                        icon={ClipboardCheck}
                        tone="emerald"
                    />
                    <StatCard
                        label="Average score"
                        value={overallAverage === null ? "-" : fmtScore(overallAverage)}
                        hint="across all submissions"
                        icon={BarChart3}
                        tone="amber"
                    />
                    <StatCard label="Locked tracks" value={`${lockedCount} / ${progress.tracks.length}`} icon={Lock} tone="gray" />
                </div>

                {/* Donuts */}
                <div className="stagger grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <Panel title="Submissions" subtitle="Submitted vs still expected, all tracks" icon={PieChart}>
                        <Donut
                            data={submissionSplit}
                            colors={STATUS_COLORS}
                            centerValue={`${overall}%`}
                            centerLabel="complete"
                            height={200}
                        />
                    </Panel>
                    <Panel title="Track status" subtitle="Where each track stands right now" icon={Target}>
                        <Donut
                            data={trackStatus}
                            colors={STATUS_COLORS}
                            centerValue={progress.tracks.length}
                            centerLabel="tracks"
                            height={200}
                        />
                    </Panel>
                    <Panel title="Papers per track" subtitle="How the programme is distributed" icon={FileText}>
                        <Donut data={papersPerTrack} centerLabel="papers" height={200} />
                    </Panel>
                </div>

                {/* Charts row 1 */}
                <div className="stagger grid gap-6 lg:grid-cols-2">
                    <Panel
                        title="Submissions by track"
                        subtitle="Green = submitted, gray = still expected"
                        icon={ClipboardCheck}
                        action={
                            <Button asChild variant="ghost" size="sm">
                                <Link href={route("admin.tracks.index")}>
                                    Locks <ArrowRight />
                                </Link>
                            </Button>
                        }
                    >
                        <ProgressBars data={trackBars} />
                    </Panel>
                    <Panel title="Submission pace" subtitle="Running total of evaluations per hour" icon={Activity}>
                        <TrendArea data={timeline} xKey="label" yKey="cumulative" name="Total submitted" />
                    </Panel>
                </div>

                {/* Charts row 2 */}
                <div className="stagger grid gap-6 lg:grid-cols-2">
                    <Panel
                        title="Average score by track"
                        subtitle="Mean of each track's paper averages, as printed on its sheet; highest in amber"
                        icon={BarChart3}
                    >
                        <SimpleBars
                            data={avgByTrack}
                            xKey="track"
                            yKey="average"
                            name="Average"
                            highlightIndex={bestTrackIndex}
                            yDomain={[0, 100]}
                        />
                    </Panel>
                    <Panel title="Score distribution" subtitle="Number of evaluations per ten-point band" icon={BarChart3}>
                        <SimpleBars data={distribution} xKey="range" yKey="count" name="Evaluations" />
                    </Panel>
                </div>

                {/* Charts row 3: criteria + top papers */}
                <div className="stagger grid gap-6 lg:grid-cols-2">
                    <Panel
                        title="Average rating per criterion"
                        subtitle="Mean rating out of each criterion's maximum (C1-C5 legend below)"
                        icon={BarChart3}
                    >
                        <SimpleBars
                            data={criterionAverages.map((c) => ({ ...c, label: `${c.code} / ${c.weight}` }))}
                            xKey="label"
                            yKey="average"
                            name="Average rating"
                        />
                        <ol className="mt-3 grid gap-x-4 gap-y-1 text-xs text-gray-600 sm:grid-cols-2">
                            {criterionAverages.map((c) => (
                                <li key={c.id} className="flex justify-between gap-2">
                                    <span className="truncate" title={c.name}>
                                        <span className="font-semibold text-gray-800">{c.code}</span> {c.name}
                                    </span>
                                    <span className="shrink-0 tabular-nums">
                                        {c.average === null ? "-" : `${fmtScore(c.average)} / ${c.weight} (${c.percent}%)`}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </Panel>
                    <Panel title="Top papers overall" subtitle="Highest average across all tracks; leader in amber" icon={Trophy}>
                        <RankedBars data={topPaperRows} labelKey="label" valueKey="average" name="Average" domain={[0, 100]} />
                    </Panel>
                </div>

                {/* Charts row 4: evaluator tendency */}
                <div className="stagger grid gap-6 lg:grid-cols-2">
                    <Panel
                        title="Scoring tendency by evaluator"
                        subtitle="Average total each evaluator gives; helps spot unusually strict or lenient panels"
                        icon={Users}
                    >
                        <RankedBars
                            data={evaluatorTendency}
                            labelKey="label"
                            valueKey="average"
                            name="Average given"
                            domain={[0, 100]}
                            emphasizeFirst={false}
                        />
                    </Panel>
                    <Panel title="Submissions per hour" subtitle="How many evaluations arrived in each hour" icon={Activity}>
                        <SimpleBars data={timeline} xKey="label" yKey="count" name="Submitted" />
                    </Panel>
                </div>

                {/* Leaders + evaluators */}
                <div className="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
                    <Panel title="Current leaders" subtitle="First-ranked paper in each track right now" icon={Award}>
                        <ul className="divide-y">
                            {leaders.map((l) => (
                                <li key={l.track_id} className="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-[11px] font-bold uppercase tracking-[0.15em] text-gray-500">
                                                {l.track}
                                            </span>
                                            {l.is_locked && (
                                                <Badge variant="warning" className="gap-1">
                                                    <Lock className="size-3" /> Final
                                                </Badge>
                                            )}
                                        </div>
                                        {l.paper ? (
                                            <>
                                                <p className="truncate text-sm font-semibold text-gray-900">
                                                    {l.paper.paper_no}{" "}
                                                    <span className="font-normal text-gray-700">{l.paper.title}</span>
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {l.paper.researcher} &middot; {l.paper.evaluations_count}/{l.evaluators_count} evaluators
                                                </p>
                                            </>
                                        ) : (
                                            <p className="text-sm text-gray-500">No submissions yet</p>
                                        )}
                                    </div>
                                    {l.paper && (
                                        <span className="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-sm font-bold tabular-nums text-amber-900">
                                            {fmtScore(l.paper.average)}
                                        </span>
                                    )}
                                    <Button asChild variant="ghost" size="icon" title="Print results">
                                        <Link href={route("admin.results.track", l.track_id)}>
                                            <Printer />
                                        </Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel
                        title="Evaluator activity"
                        subtitle="Papers submitted per evaluator"
                        icon={Users}
                        action={
                            <Button asChild variant="ghost" size="sm">
                                <Link href={route("admin.evaluators.index")}>
                                    Manage <ArrowRight />
                                </Link>
                            </Button>
                        }
                    >
                        {evaluators.length === 0 ? (
                            <p className="py-6 text-center text-sm text-gray-500">No evaluators yet.</p>
                        ) : (
                            <ul className="space-y-3">
                                {evaluators.map((e) => (
                                    <li key={e.id}>
                                        <div className="mb-1 flex items-center justify-between gap-2 text-sm">
                                            <span className="truncate font-medium text-gray-900">{e.name}</span>
                                            <span className="shrink-0 text-xs text-gray-500">
                                                {e.submitted}/{e.expected}
                                                {e.average !== null && (
                                                    <span className="ml-2 text-gray-400">avg {fmtScore(e.average)}</span>
                                                )}
                                            </span>
                                        </div>
                                        <Progress value={e.percent} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                {/* Recent */}
                <Panel title="Recent submissions" subtitle="Latest evaluations received" icon={Activity}>
                    {recent.length === 0 ? (
                        <p className="py-6 text-center text-sm text-gray-500">Nothing submitted yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[520px] text-sm">
                                <thead className="text-left text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th className="pb-2 font-medium">Evaluator</th>
                                        <th className="pb-2 font-medium">Paper</th>
                                        <th className="pb-2 text-right font-medium">Total</th>
                                        <th className="pb-2 text-right font-medium">When</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {recent.map((r) => (
                                        <tr key={r.id}>
                                            <td className="py-2 text-gray-900">{r.evaluator}</td>
                                            <td className="py-2">
                                                <Link
                                                    href={route("admin.results.paper", r.paper_id)}
                                                    className="font-semibold text-emerald-800 hover:underline"
                                                >
                                                    {r.paper_no}
                                                </Link>{" "}
                                                <span className="text-gray-600">{r.title}</span>
                                            </td>
                                            <td className="py-2 text-right font-semibold tabular-nums">{fmtScore(r.total)}</td>
                                            <td className="py-2 text-right text-xs text-gray-500">{fmtDateTime(r.submitted_at)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}
