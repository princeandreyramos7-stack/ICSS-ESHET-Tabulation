/** Translucent bordered panel used on the dark brand pages. */
export default function GlassCard({ children, className = "", as: Tag = "div", ...props }) {
    return (
        <Tag
            className={`rounded-2xl border border-emerald-200/15 bg-white/[0.06] shadow-[0_8px_30px_rgba(0,0,0,0.25)] backdrop-blur-sm ${className}`}
            {...props}
        >
            {children}
        </Tag>
    );
}
