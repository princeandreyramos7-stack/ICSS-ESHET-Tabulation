/** Formats a score for display. null/undefined becomes a dash. */
export function fmtScore(value, digits = 2) {
    if (value === null || value === undefined || value === "") return "-";
    const n = Number(value);
    if (Number.isNaN(n)) return "-";
    return n.toFixed(digits);
}

/** Formats a server datetime string ("YYYY-MM-DD HH:MM:SS") for humans. */
export function fmtDateTime(value) {
    if (!value) return "-";
    const d = new Date(value.replace(" ", "T"));
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

/** Ordinal suffix for ranks: 1st, 2nd, 3rd... */
export function ordinal(n) {
    if (n === null || n === undefined) return "-";
    const s = ["th", "st", "nd", "rd"];
    const v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
}
