import { Head, Link, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    CalendarDays,
    ClipboardCheck,
    LockKeyhole,
    LogIn,
    Mail,
    MapPin,
    MonitorSmartphone,
    Phone,
    Printer,
    ShieldCheck,
} from "lucide-react";

import BrandBackdrop from "@/Components/Brand/BrandBackdrop";
import ConferenceLogo from "@/Components/Brand/ConferenceLogo";
import DeveloperCredit from "@/Components/Brand/DeveloperCredit";
import Eyebrow from "@/Components/Brand/Eyebrow";
import GlassCard from "@/Components/Brand/GlassCard";
import LogoTrio from "@/Components/Brand/LogoTrio";
import { LOGOS } from "@/lib/brand";
import { useCountdown } from "@/hooks/use-countdown";

function InfoChip({ icon: Icon, children }) {
    return (
        <span className="inline-flex items-center gap-2 rounded-xl border border-emerald-200/15 bg-white/[0.06] px-3.5 py-2.5 text-sm font-medium text-emerald-50">
            <Icon className="size-4 shrink-0 text-amber-400" />
            {children}
        </span>
    );
}

function CountdownCard({ startsAt, dates }) {
    const { days, hours, minutes, seconds, done } = useCountdown(startsAt);
    const units = [
        ["Days", days],
        ["Hours", hours],
        ["Minutes", minutes],
        ["Seconds", seconds],
    ];
    return (
        <GlassCard className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <Eyebrow>{done ? "Conference" : "Conference starts in"}</Eyebrow>
                <p className="mt-1 text-lg font-bold">{done ? "In progress" : dates}</p>
            </div>
            {!done && (
                <div className="grid grid-cols-4 gap-2">
                    {units.map(([label, value]) => (
                        <div
                            key={label}
                            className="rounded-xl border border-emerald-200/15 bg-emerald-950/50 px-2 py-2 text-center"
                        >
                            <p className="text-xl font-bold tabular-nums text-amber-400">
                                {String(value).padStart(2, "0")}
                            </p>
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-emerald-200">
                                {label}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </GlassCard>
    );
}

export default function Welcome({ tracks, criteria }) {
    const { conference: c } = usePage().props;
    const totalWeight = criteria.reduce((s, x) => s + x.weight, 0);

    const steps = [
        {
            icon: LogIn,
            title: "Sign in with your panel account",
            text: "Accounts are issued by the conference secretariat. No registration is needed.",
        },
        {
            icon: ClipboardCheck,
            title: "Rate each presentation",
            text: "Open a track, step through its papers, and score the five official criteria. Ratings save per paper and can be revised until the track is locked.",
        },
        {
            icon: Printer,
            title: "Results are tabulated instantly",
            text: "Averages and rankings are computed the moment scores come in, ready for the printed result sheets.",
        },
    ];

    return (
        <BrandBackdrop>
            <Head title="Welcome" />

            {/* Top bar */}
            <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <Link href={route("welcome")} className="flex items-center gap-3">
                    <ConferenceLogo className="size-11" />
                    <span className="leading-tight">
                        <span className="block text-sm font-extrabold tracking-wide">{c.acronym}</span>
                        <span className="block text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-200">
                            Tabulation System
                        </span>
                    </span>
                </Link>
                <Link
                    href={route("login")}
                    className="inline-flex items-center gap-2 rounded-full bg-amber-400 px-5 py-2.5 text-sm font-bold text-emerald-950 shadow-lg shadow-amber-900/30 transition hover:bg-amber-300"
                >
                    <LogIn className="size-4" />
                    Sign in
                </Link>
            </header>

            {/* Hero */}
            <section className="stagger mx-auto grid max-w-6xl gap-10 px-5 pb-16 pt-8 sm:px-8 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:pt-14">
                <div>
                    <LogoTrio size="size-14" emphasis="size-[4.5rem]" className="mb-6" />
                    <Eyebrow>Panel of Evaluators</Eyebrow>
                    <p className="mt-3 text-sm font-semibold text-emerald-200">{c.edition}</p>
                    <h1 className="mt-1 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-[3.4rem]">
                        {c.name.replace(/^\d+(st|nd|rd|th) International Conference on /i, "")}
                    </h1>
                    <p className="mt-2 text-lg font-bold text-amber-400">{c.theme}</p>
                    <p className="mt-4 max-w-xl text-sm leading-relaxed text-emerald-100/90 sm:text-base">
                        {c.tagline}
                    </p>

                    <div className="mt-6 flex flex-wrap gap-2">
                        <InfoChip icon={CalendarDays}>{c.dates}</InfoChip>
                        <InfoChip icon={MapPin}>{c.venue}</InfoChip>
                        <InfoChip icon={MonitorSmartphone}>{c.format}</InfoChip>
                    </div>

                    <div className="mt-8 flex flex-wrap gap-3">
                        <Link
                            href={route("login")}
                            className="inline-flex items-center gap-2 rounded-full bg-amber-400 px-6 py-3 text-base font-bold text-emerald-950 shadow-lg shadow-amber-900/30 transition hover:bg-amber-300"
                        >
                            Evaluator sign in
                            <ArrowRight className="size-4" />
                        </Link>
                        <a
                            href={c.website}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-full border border-white/40 px-6 py-3 text-base font-semibold text-white transition hover:bg-white/10"
                        >
                            Conference website
                        </a>
                    </div>
                </div>

                <div className="space-y-4">
                    <GlassCard className="overflow-hidden">
                        <div className="relative aspect-[16/10]">
                            <img
                                src={LOGOS.campusPhoto.src}
                                alt={LOGOS.campusPhoto.alt}
                                className="h-full w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-emerald-950/95 via-emerald-950/40 to-transparent" />
                            <div className="absolute inset-x-0 bottom-0 p-5">
                                <Eyebrow>{c.organizer}</Eyebrow>
                                <p className="mt-1 text-lg font-bold leading-snug">{c.campus}</p>
                                <p className="text-xs text-emerald-200">Host of {c.acronym}</p>
                            </div>
                        </div>
                    </GlassCard>
                    <CountdownCard startsAt={c.starts_at} dates={c.dates} />
                </div>
            </section>

            {/* Tracks */}
            <section className="mx-auto max-w-6xl px-5 py-12 sm:px-8">
                <div className="text-center">
                    <Eyebrow>Research Key Themes</Eyebrow>
                    <h2 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Presentation Tracks</h2>
                    <p className="mx-auto mt-2 max-w-2xl text-sm text-emerald-100/80">
                        Every paper is presented under one of these tracks and evaluated by the full panel.
                    </p>
                </div>
                <ol className="stagger mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {tracks.map((t) => (
                        <GlassCard as="li" key={t.number} className="lift p-5 hover:bg-white/[0.09]">
                            <Eyebrow>{String(t.number).padStart(2, "0")} / Track</Eyebrow>
                            <p className="mt-2 text-base font-bold leading-snug">{t.name}</p>
                        </GlassCard>
                    ))}
                </ol>
            </section>

            {/* Criteria + steps */}
            <section className="mx-auto grid max-w-6xl gap-6 px-5 py-12 sm:px-8 lg:grid-cols-[1.1fr_1fr]">
                <GlassCard className="p-6">
                    <Eyebrow>Evaluation Rubric</Eyebrow>
                    <h2 className="mt-2 text-2xl font-extrabold tracking-tight">How presentations are scored</h2>
                    <p className="mt-1 text-sm text-emerald-100/80">
                        Each criterion is rated up to its percentage weight. Total {totalWeight} points.
                    </p>
                    <ol className="mt-5 divide-y divide-emerald-200/10">
                        {criteria.map((cr, i) => (
                            <li key={cr.name} className="flex items-center gap-4 py-3">
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-amber-300">
                                    {i + 1}
                                </span>
                                <span className="flex-1 text-sm font-medium leading-snug">{cr.name}</span>
                                <span className="shrink-0 rounded-full bg-amber-400 px-3 py-1 text-sm font-bold text-emerald-950">
                                    {cr.weight}%
                                </span>
                            </li>
                        ))}
                    </ol>
                </GlassCard>

                <div className="space-y-4">
                    <div>
                        <Eyebrow>For Evaluators</Eyebrow>
                        <h2 className="mt-2 text-2xl font-extrabold tracking-tight">How it works</h2>
                    </div>
                    {steps.map((s, i) => (
                        <GlassCard key={s.title} className="flex gap-4 p-5">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-800 text-amber-300">
                                <s.icon className="size-5" />
                            </span>
                            <div>
                                <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">
                                    Step {i + 1}
                                </p>
                                <p className="mt-0.5 font-bold">{s.title}</p>
                                <p className="mt-1 text-sm leading-relaxed text-emerald-100/80">{s.text}</p>
                            </div>
                        </GlassCard>
                    ))}
                    <div className="flex items-start gap-3 rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-100">
                        <ShieldCheck className="mt-0.5 size-5 shrink-0 text-amber-400" />
                        <p>
                            Scores are private to the panel. Only the administrator can view tabulated results, and
                            tracks are locked once presentations conclude.
                            <LockKeyhole className="ml-1 inline size-3.5 -translate-y-px" />
                        </p>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-emerald-200/10 bg-emerald-950/60">
                <div className="mx-auto grid max-w-6xl gap-8 px-5 py-10 sm:px-8 md:grid-cols-[1.4fr_1fr_1fr]">
                    <div>
                        <LogoTrio size="size-10" emphasis="size-12" className="mb-4" />
                        <p className="text-base font-extrabold uppercase tracking-wide">{c.organizer}</p>
                        <p className="text-sm font-semibold text-amber-400">{c.campus}</p>
                        <p className="mt-2 max-w-sm text-xs leading-relaxed text-emerald-100/70">{c.name}</p>
                    </div>
                    <div className="space-y-3 text-sm">
                        <p className="flex items-start gap-2">
                            <MapPin className="mt-0.5 size-4 shrink-0 text-amber-400" />
                            <span>
                                <Eyebrow>Location</Eyebrow>
                                {c.venue}
                            </span>
                        </p>
                        <p className="flex items-start gap-2">
                            <Phone className="mt-0.5 size-4 shrink-0 text-amber-400" />
                            <span>
                                <Eyebrow>Contact</Eyebrow>
                                {c.phone}
                            </span>
                        </p>
                    </div>
                    <div className="space-y-3 text-sm">
                        <p className="flex items-start gap-2">
                            <Mail className="mt-0.5 size-4 shrink-0 text-amber-400" />
                            <span>
                                <Eyebrow>Email</Eyebrow>
                                <a href={`mailto:${c.email}`} className="hover:underline">
                                    {c.email}
                                </a>
                            </span>
                        </p>
                        <p className="flex items-start gap-2">
                            <CalendarDays className="mt-0.5 size-4 shrink-0 text-amber-400" />
                            <span>
                                <Eyebrow>Schedule</Eyebrow>
                                {c.dates}
                            </span>
                        </p>
                    </div>
                </div>
                <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 border-t border-emerald-200/10 px-5 py-5 sm:flex-row sm:px-8">
                    <p className="text-xs text-emerald-100/50">
                        &copy; {c.year} {c.acronym}. Tabulation system for the Panel of Evaluators.
                    </p>
                    <DeveloperCredit tone="dark" />
                </div>
            </footer>
        </BrandBackdrop>
    );
}
