import { usePage } from "@inertiajs/react";

import LogoTrio from "@/Components/Brand/LogoTrio";

/**
 * Letterhead printed at the top of every result sheet:
 * the three official seals, conference name, theme, and the track band.
 */
export default function PrintSheetHeader({ subtitle, trackLabel, venue }) {
    const { conference } = usePage().props;

    return (
        <div className="print-compact mb-4 border-b-2 border-emerald-900 pb-3 text-center">
            <LogoTrio size="size-12" emphasis="size-16" light={false} className="mb-2 justify-center" />
            <p className="text-xs uppercase tracking-widest text-gray-500">
                {conference?.organizer} &middot; {conference?.campus}
            </p>
            <h2 className="mt-1 text-lg font-bold leading-tight text-emerald-900 sm:text-xl">
                {conference?.name}
            </h2>
            <p className="text-xs font-semibold uppercase tracking-widest text-amber-600 sm:text-sm">
                {conference?.theme} &middot; {conference?.dates} &middot; {conference?.venue}
            </p>
            {trackLabel && (
                <p className="mt-2 inline-block rounded-md bg-amber-400 px-4 py-1 text-sm font-semibold text-emerald-950">
                    {trackLabel}
                </p>
            )}
            {venue && <p className="mt-1 text-xs text-gray-600">Venue: {venue}</p>}
            {subtitle && (
                <p className="mt-2 text-base font-semibold text-gray-800">{subtitle}</p>
            )}
        </div>
    );
}
