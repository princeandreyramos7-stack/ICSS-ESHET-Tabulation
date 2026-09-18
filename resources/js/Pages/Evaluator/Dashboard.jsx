import { Head, Link, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    Check,
    CheckCircle2,
    ClipboardCheck,
    ClipboardList,
    Cog,
    Cpu,
    GraduationCap,
    HeartPulse,
    Leaf,
    Lock,
    MapPin,
    MonitorSmartphone,
    Palette,
    Pencil,
    Scale,
} from "lucide-react";

import Eyebrow from "@/Components/Brand/Eyebrow";
import LogoTrio from "@/Components/Brand/LogoTrio";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";
import { useCountdown } from "@/hooks/use-countdown";
import AppLayout from "@/Layouts/AppLayout";
import { LOGOS } from "@/lib/brand";
import { fmtDateTime, fmtScore } from "@/lib/format";

function iconFor(name) {
    const n = name.toLowerCase();
    if (/criminolog|legal|justice|law/.test(n)) return Scale;
    if (/health|medic|nurs/.test(n)) return HeartPulse;
    if (/comput|software|ict|informat/.test(n)) return Cpu;
    if (/educat|social|teach|learn/.test(n)) return GraduationCap;
    if (/art|architect|design|humanit/.test(n)) return Palette;
    if (/energy|environment|renewable|climate|agricult/.test(n)) return Leaf;
    if (/engineer|sustainab/.test(n)) return Cog;
    return BookOpen;
}

function CountdownChips({ startsAt }) {
    const { days, hours, minutes, seconds, done } = useCountdown(startsAt);
    if (done) {
        return <p className="text-lg font-bold text-amber-300">Conference in progress</p>;
    }
    const units = [
        ["Days", days],
        ["Hours", hours],
        ["Min", minutes],
        ["Sec", seconds],
    ];
    return (
        <div className="grid grid-cols-4 gap-2">
            {units.map(([label, value]) => (
                <div key={label} className="rounded-xl border border-emerald-200/15 bg-emerald-950/50 px-2 py-2 text-center">
                    <p className="text-xl font-bold tabular-nums text-amber-400">{String(value).padStart(2, "0")}</p>
                    <p className="text-[10px] font-semibold uppercase tracking-wider text-emerald-200">{label}</p>
                </div>
            ))}
        </div>
    );
}

function InfoChip({ icon: Icon, children }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-xl border border-emerald-200/15 bg-white/[0.06] px-3 py-2 text-sm font-medium text-emerald-50">
            <Icon className="size-4 shrink-0 text-amber-400" />
            {children}
        </span>
    );
}

export default function Dashboard({ tracks, totals, next_paper: nextPaper, recent, criteria }) {
    const { auth, conference: c } = usePage().props;
    const percent = totals.papers ? Math.round((totals.evaluated / totals.papers) * 100) : 0;
    const allDone = totals.papers > 0 && totals.pending === 0;

    const startHref = nextPaper
        ? route("evaluator.workspace", { track: nextPaper.track_id, paper: nextPaper.id })
        : route("evaluator.workspace");

    return (
        <AppLayout breadcrumbs={[{ label: "Dashboard" }]}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl space-y-8">
                {/* Hero */}
                <section
                    className="relative overflow-hidden rounded-3xl text-white shadow-lg"
                    style={{
                        backgroundImage:
                            "radial-gradient(60% 60% at 90% 0%, rgba(52,211,153,0.28) 0%, rgba(6,38,26,0) 60%), linear-gradient(160deg, #0b3d2a 0%, #06261a 60%, #041c13 100%)",
                    }}
                >
                    <img
                        src={LOGOS.campusPhoto.src}
                        alt=""
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-[0.12]"
                    />
                    <div aria-hidden="true" className="pointer-events-none absolute -right-20 -top-20 size-72 rounded-full border border-amber-300/20" />
                    <div aria-hidden="true" className="pointer-events-none absolute -left-24 bottom-0 size-64 rounded-full border border-emerald-300/10" />

                    <div className="stagger relative grid gap-8 p-6 sm:p-8 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:p-10">
                        <div>
                            <LogoTrio size="size-12" emphasis="size-16" className="mb-5" />
                            <Eyebrow>Panel of Evaluators &middot; {c?.edition}</Eyebrow>
                            <h1 className="mt-2 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
                                {c?.name?.replace(/^\d+(st|nd|rd|th) International Conference on /i, "")}
                            </h1>
                            <p className="mt-1 text-lg font-bold text-amber-400">{c?.theme}</p>
                            <p className="mt-3 max-w-xl text-sm text-emerald-100/90">
                                Welcome, <strong className="text-white">{auth.user.name}</strong>. Thank you for serving on
                                the panel. Choose a track below to begin rating presentations.
                            </p>
                            <div className="mt-5 flex flex-wrap gap-2">
                                <InfoChip icon={CalendarDays}>{c?.dates}</InfoChip>
                                <InfoChip icon={MapPin}>{c?.venue}</InfoChip>
                                <InfoChip icon={MonitorSmartphone}>{c?.format}</InfoChip>
                            </div>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Button asChild size="lg" className="rounded-full bg-amber-400 px-6 text-base font-bold text-emerald-950 hover:bg-amber-300">
                                    <Link href={startHref}>
                                        {allDone ? "Review my evaluations" : totals.evaluated > 0 ? "Continue evaluating" : "Start evaluating"}
                                        <ArrowRight />
                                    </Link>
                                </Button>
                                {nextPaper && (
                                    <span className="inline-flex items-center text-sm text-emerald-100">
                                        Up next: <strong className="ml-1 text-white">{nextPaper.paper_no}</strong>
                                        <span className="ml-1 hidden sm:inline">&middot; Track {nextPaper.track_number}</span>
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="rounded-2xl border border-emerald-200/15 bg-white/[0.06] p-5 backdrop-blur-sm">
                                <Eyebrow>Conference starts in</Eyebrow>
                                <div className="mt-3">
                                    <CountdownChips startsAt={c?.starts_at} />
                                </div>
                            </div>
                            <div className="rounded-2xl border border-emerald-200/15 bg-white/[0.06] p-5 backdrop-blur-sm">
                                <div className="mb-2 flex items-end justify-between">
                                    <div>
                                        <Eyebrow>Your progress</Eyebrow>
                                        <p className="mt-1 text-3xl font-extrabold tabular-nums">
                                            {percent}
                                            <span className="text-lg font-semibold text-emerald-200">%</span>
                                        </p>
                                    </div>
                                    <p className="text-right text-sm text-emerald-100">
                                        <strong className="text-white">{totals.evaluated}</strong> of {totals.papers} papers
                                        <br />
                                        <span className="text-xs text-emerald-200">{totals.pending} pending</span>
                                    </p>
                                </div>
                                <Progress value={percent} className="bg-emerald-950/50" barClassName="bg-amber-400" />
                            </div>
                        </div>
                    </div>
                </section>

                {/* Tracks */}
                <section>
                    <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <Eyebrow className="!text-amber-600">Research Key Themes</Eyebrow>
                            <h2 className="text-2xl font-extrabold tracking-tight text-gray-900">Choose a track</h2>
                        </div>
                        <p className="text-sm text-gray-500">
                            {totals.locked_tracks > 0 && `${totals.locked_tracks} locked · `}
                            {tracks.length} tracks
                        </p>
                    </div>
                    <ol className="stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {tracks.map((t) => {
                            const p = t.papers_count ? Math.round((t.evaluated_count / t.papers_count) * 100) : 0;
                            const complete = t.papers_count > 0 && p === 100;
                            const Icon = iconFor(t.name);
                            return (
                                <li key={t.id}>
                                    <Link
                                        href={route("evaluator.workspace", { track: t.id, paper: t.next_paper_id ?? undefined })}
                                        className="lift group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:border-emerald-400"
                                    >
                                        <Icon aria-hidden="true" className="pointer-events-none absolute -bottom-4 -right-4 size-24 text-emerald-900/[0.05]" />
                                        <div className="flex items-start justify-between gap-2">
                                            <span className="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 transition group-hover:bg-emerald-800 group-hover:text-amber-300">
                                                <Icon className="size-5" />
                                            </span>
                                            {t.is_locked ? (
                                                <Badge variant="warning" className="gap-1">
                                                    <Lock className="size-3" /> Locked
                                                </Badge>
                                            ) : complete ? (
                                                <Badge variant="success" className="gap-1">
                                                    <Check className="size-3" /> Done
                                                </Badge>
                                            ) : t.evaluated_count > 0 ? (
                                                <Badge variant="secondary">In progress</Badge>
                                            ) : null}
                                        </div>
                                        <p className="mt-4 text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600">
                                            Track {t.number}
                                        </p>
                                        <p className="mt-1 flex-1 text-base font-bold leading-snug text-gray-900">{t.name}</p>
                                        <div className="relative mt-4">
                                            <div className="mb-1 flex justify-between text-xs text-gray-500">
                                                <span>
                                                    {t.evaluated_count} of {t.papers_count} evaluated
                                                </span>
                                                <span className="font-semibold text-gray-700">{p}%</span>
                                            </div>
                                            <Progress value={p} />
                                        </div>
                                        <span className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-800">
                                            {t.is_locked ? "View ratings" : complete ? "Review" : t.papers_count === 0 ? "No papers yet" : "Evaluate"}
                                            <ArrowRight className="size-4 transition group-hover:translate-x-0.5" />
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ol>
                </section>

                {/* Recent + rubric */}
                <section className="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                    <Card className="shadow-sm">
                        <CardContent className="p-0">
                            <div className="flex items-center justify-between border-b px-5 py-4">
                                <div>
                                    <h3 className="font-semibold text-gray-900">Recent submissions</h3>
                                    <p className="text-xs text-gray-500">Your latest evaluations</p>
                                </div>
                                <ClipboardCheck className="size-5 text-emerald-700" />
                            </div>
                            {recent.length === 0 ? (
                                <div className="px-5 py-10 text-center">
                                    <ClipboardList className="mx-auto mb-2 size-8 text-gray-300" />
                                    <p className="text-sm text-gray-500">Your submitted evaluations will appear here.</p>
                                </div>
                            ) : (
                                <ul className="divide-y">
                                    {recent.map((r) => (
                                        <li key={r.paper_id} className="flex items-center gap-3 px-5 py-3">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800">
                                                {fmtScore(r.total, 0)}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold text-gray-900">
                                                    {r.paper_no} <span className="font-normal text-gray-600">{r.title}</span>
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    Track {r.track_number} &middot; {fmtDateTime(r.submitted_at)}
                                                </p>
                                            </div>
                                            <Button asChild variant="ghost" size="sm" title={r.track_locked ? "View" : "Revise"}>
                                                <Link href={route("evaluator.workspace", { track: r.track_id, paper: r.paper_id })}>
                                                    {r.track_locked ? <Lock /> : <Pencil />}
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardContent className="p-0">
                            <div className="border-b px-5 py-4">
                                <h3 className="font-semibold text-gray-900">Rubric at a glance</h3>
                                <p className="text-xs text-gray-500">Rate each criterion up to its weight. Total 100.</p>
                            </div>
                            <ol className="divide-y">
                                {criteria.map((cr, i) => (
                                    <li key={cr.name} className="flex items-center gap-3 px-5 py-2.5 text-sm">
                                        <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-amber-300">
                                            {i + 1}
                                        </span>
                                        <span className="flex-1 leading-snug text-gray-800">{cr.name}</span>
                                        <Badge variant="warning">{cr.weight}%</Badge>
                                    </li>
                                ))}
                            </ol>
                            {allDone && (
                                <p className="flex items-center gap-2 border-t bg-emerald-50 px-5 py-3 text-sm text-emerald-900">
                                    <CheckCircle2 className="size-4" /> All papers evaluated. Thank you!
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
