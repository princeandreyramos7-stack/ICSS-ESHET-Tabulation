import { useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { ArrowLeft, CalendarDays, MapPin, MonitorSmartphone } from "lucide-react";
import { toast } from "sonner";

import BrandBackdrop from "@/Components/Brand/BrandBackdrop";
import ConferenceLogo from "@/Components/Brand/ConferenceLogo";
import DeveloperCredit from "@/Components/Brand/DeveloperCredit";
import Eyebrow from "@/Components/Brand/Eyebrow";
import LogoTrio from "@/Components/Brand/LogoTrio";
import { Toaster } from "@/Components/ui/sonner";
import { LOGOS } from "@/lib/brand";

/**
 * Shell for unauthenticated pages: branded panel on the left, form on the right.
 */
export default function GuestLayout({ children }) {
    const { conference: c, flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    const facts = [
        [CalendarDays, c?.dates],
        [MapPin, c?.venue],
        [MonitorSmartphone, c?.format],
    ];

    return (
        <BrandBackdrop className="flex flex-col">
            <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href={route("welcome")} className="flex items-center gap-3">
                    <ConferenceLogo className="size-10" />
                    <span className="leading-tight">
                        <span className="block text-sm font-extrabold tracking-wide">{c?.acronym}</span>
                        <span className="block text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-200">
                            Tabulation System
                        </span>
                    </span>
                </Link>
                <Link
                    href={route("welcome")}
                    className="inline-flex items-center gap-1.5 text-sm text-emerald-100/80 transition hover:text-white"
                >
                    <ArrowLeft className="size-4" />
                    Back to home
                </Link>
            </div>

            <div className="flex flex-1 flex-col items-center justify-center gap-6 px-4 pb-10 pt-4 sm:px-6">
                <div className="page-enter grid w-full max-w-4xl overflow-hidden rounded-3xl border border-emerald-200/15 shadow-2xl shadow-black/40 md:grid-cols-[1.05fr_1fr]">
                    {/* Brand panel */}
                    <div className="relative hidden md:block">
                        <img
                            src={LOGOS.campusPhoto.src}
                            alt="Isabela State University, City of Ilagan Campus"
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-br from-emerald-950/95 via-emerald-900/85 to-emerald-950/95" />
                        <div className="relative flex h-full flex-col justify-between p-8">
                            <div>
                                <LogoTrio size="size-12" emphasis="size-16" className="mb-6" />
                                <Eyebrow>{c?.edition}</Eyebrow>
                                <h2 className="mt-3 text-2xl font-extrabold leading-tight tracking-tight">
                                    {c?.name?.replace(/^\d+(st|nd|rd|th) International Conference on /i, "")}
                                </h2>
                                <p className="mt-1 text-base font-bold text-amber-400">{c?.theme}</p>
                            </div>
                            <div className="space-y-2">
                                {facts.map(([Icon, text]) => (
                                    <p key={text} className="flex items-center gap-2 text-sm text-emerald-50">
                                        <Icon className="size-4 shrink-0 text-amber-400" />
                                        {text}
                                    </p>
                                ))}
                                <p className="pt-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-200/80">
                                    {c?.organizer} &middot; {c?.campus}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form panel */}
                    <div className="bg-white p-6 text-gray-900 sm:p-8 md:p-10">{children}</div>
                </div>
                <DeveloperCredit tone="dark" compact />
            </div>

            <Toaster theme="light" position="top-center" offset={{ top: 72 }} mobileOffset={{ top: 72 }} richColors closeButton />
        </BrandBackdrop>
    );
}
