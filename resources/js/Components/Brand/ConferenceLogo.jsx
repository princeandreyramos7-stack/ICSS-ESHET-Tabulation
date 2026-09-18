import { LOGOS } from "@/lib/brand";

/** Round conference emblem. */
export default function ConferenceLogo({ className = "size-12", ring = true }) {
    return (
        <span
            className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ${
                ring ? "ring-2 ring-amber-400/80" : ""
            } ${className}`}
        >
            <img src={LOGOS.conference.src} alt={LOGOS.conference.alt} className="h-full w-full object-cover" />
        </span>
    );
}
