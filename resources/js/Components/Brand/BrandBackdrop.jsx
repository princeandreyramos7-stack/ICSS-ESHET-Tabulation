/**
 * Full-page emerald backdrop with soft light blooms and ring outlines,
 * in the spirit of the conference website. Children render on top.
 */
export default function BrandBackdrop({ children, className = "" }) {
    return (
        <div
            className={`relative min-h-svh overflow-hidden bg-[#06261a] text-white ${className}`}
            style={{
                backgroundImage:
                    "radial-gradient(60% 50% at 85% 10%, rgba(52,211,153,0.28) 0%, rgba(6,38,26,0) 60%), radial-gradient(45% 40% at 5% 90%, rgba(16,185,129,0.22) 0%, rgba(6,38,26,0) 60%), linear-gradient(160deg, #0b3d2a 0%, #06261a 55%, #041c13 100%)",
            }}
        >
            <div aria-hidden="true" className="pointer-events-none absolute -left-40 top-1/3 size-[420px] rounded-full border border-emerald-300/15" />
            <div aria-hidden="true" className="pointer-events-none absolute -left-24 top-1/3 mt-16 size-[300px] rounded-full border border-emerald-300/10" />
            <div aria-hidden="true" className="pointer-events-none absolute -right-32 -top-32 size-[380px] rounded-full border border-amber-300/10" />
            <div className="relative">{children}</div>
        </div>
    );
}
