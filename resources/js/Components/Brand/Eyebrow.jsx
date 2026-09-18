/** Small uppercase gold label above headings, e.g. "01 / PURPOSE". */
export default function Eyebrow({ children, className = "" }) {
    return (
        <p className={`text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400 ${className}`}>
            {children}
        </p>
    );
}
