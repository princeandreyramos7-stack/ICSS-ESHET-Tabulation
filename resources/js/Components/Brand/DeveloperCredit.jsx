import { usePage } from "@inertiajs/react";

/**
 * "Developed by" credit for the organization that built the system.
 * Reads config/conference.php -> developer. Renders nothing if no name is set.
 * `tone` = "dark" (on the green backdrop) or "light" (on white cards).
 */
export default function DeveloperCredit({ tone = "dark", compact = false, className = "" }) {
    const dev = usePage().props.conference?.developer;
    if (!dev?.name) return null;

    const dark = tone === "dark";
    const label = compact ? dev.short_name || dev.name : dev.name;

    const body = (
        <>
            {dev.logo && (
                <img
                    src={dev.logo}
                    alt={`${dev.short_name || dev.name} logo`}
                    className={compact ? "size-7 shrink-0 object-contain" : "size-10 shrink-0 object-contain"}
                    onError={(e) => {
                        e.currentTarget.style.display = "none";
                    }}
                />
            )}
            <span className="leading-tight">
                <span className={`block text-[10px] font-semibold uppercase tracking-[0.2em] ${dark ? "text-emerald-200/70" : "text-gray-400"}`}>
                    Developed by
                </span>
                <span className={`block text-sm font-bold ${dark ? "text-white" : "text-gray-800"}`}>
                    {label}
                    {!compact && dev.short_name && dev.short_name !== dev.name && (
                        <span className={`ml-1 font-semibold ${dark ? "text-amber-300" : "text-amber-600"}`}>({dev.short_name})</span>
                    )}
                </span>
                {!compact && dev.tagline && (
                    <span className={`block text-[11px] ${dark ? "text-emerald-100/60" : "text-gray-500"}`}>{dev.tagline}</span>
                )}
            </span>
        </>
    );

    const classes = `inline-flex items-center gap-3 ${className}`;

    return dev.url ? (
        <a href={dev.url} target="_blank" rel="noreferrer" className={`${classes} transition hover:opacity-90`}>
            {body}
        </a>
    ) : (
        <span className={classes}>{body}</span>
    );
}
