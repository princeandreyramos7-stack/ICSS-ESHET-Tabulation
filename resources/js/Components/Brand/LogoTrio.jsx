import { LOGOS } from "@/lib/brand";

/**
 * The three seals from the official flyer: university, conference, city.
 * `size` is a Tailwind size class for the side seals; the conference seal is larger.
 */
export default function LogoTrio({ size = "size-12", emphasis = "size-16", className = "", light = true }) {
    const items = [
        [LOGOS.university, size],
        [LOGOS.conference, emphasis],
        [LOGOS.city, size],
    ];
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            {items.map(([logo, cls]) => (
                <span
                    key={logo.src}
                    className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ${
                        light ? "ring-2 ring-white/70 shadow-lg shadow-black/30" : "ring-1 ring-gray-300"
                    } ${cls}`}
                >
                    <img src={logo.src} alt={logo.alt} className="h-full w-full object-cover" />
                </span>
            ))}
        </div>
    );
}
