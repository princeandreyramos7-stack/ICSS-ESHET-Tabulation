import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from "recharts";

/**
 * Chart primitives for the admin dashboard. One hue (emerald) for magnitude,
 * amber only for emphasis, gray for the de-emphasised remainder. Text stays in
 * text tokens; marks carry the color.
 */
export const CHART = {
    series: "#047857", // emerald-700
    seriesSoft: "#6ee7b7", // emerald-300
    accent: "#d97706", // amber-600
    muted: "#e5e7eb", // gray-200 (remaining / track)
    grid: "#f3f4f6",
    axis: "#6b7280",
};

function TooltipBox({ active, payload, label, format }) {
    if (!active || !payload?.length) return null;
    return (
        <div className="rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-md">
            <p className="font-semibold text-gray-900">{label}</p>
            {payload.map((p) => (
                <p key={p.dataKey} className="text-gray-600">
                    {p.name}: <span className="font-semibold text-gray-900">{format ? format(p) : p.value}</span>
                </p>
            ))}
        </div>
    );
}

export function EmptyChart({ children = "No data yet." }) {
    return (
        <div className="flex h-56 items-center justify-center rounded-md border border-dashed text-sm text-gray-500">
            {children}
        </div>
    );
}

/** Horizontal bars: value against a max, drawn as fill over a muted track. */
export function ProgressBars({ data, valueKey = "submitted", maxKey = "expected", labelKey = "label", height }) {
    if (!data?.length) return <EmptyChart />;
    const rows = data.map((d) => ({
        ...d,
        remaining: Math.max(0, (d[maxKey] ?? 0) - (d[valueKey] ?? 0)),
    }));
    return (
        <ResponsiveContainer width="100%" height={height ?? Math.max(160, rows.length * 34 + 24)}>
            <BarChart data={rows} layout="vertical" margin={{ top: 4, right: 40, bottom: 4, left: 4 }} barCategoryGap={8}>
                <CartesianGrid horizontal={false} stroke={CHART.grid} />
                <XAxis type="number" hide />
                <YAxis
                    type="category"
                    dataKey={labelKey}
                    width={56}
                    tick={{ fontSize: 12, fill: CHART.axis }}
                    axisLine={false}
                    tickLine={false}
                />
                <Tooltip
                    cursor={{ fill: "rgba(0,0,0,0.03)" }}
                    content={<TooltipBox format={(p) => (p.dataKey === "remaining" ? p.value : p.value)} />}
                />
                <Bar dataKey={valueKey} name="Submitted" stackId="a" fill={CHART.series} radius={[0, 4, 4, 0]} />
                <Bar dataKey="remaining" name="Remaining" stackId="a" fill={CHART.muted} radius={[0, 4, 4, 0]} />
            </BarChart>
        </ResponsiveContainer>
    );
}

/** Vertical bars for a single measure; optional highlight index in amber. */
export function SimpleBars({ data, xKey, yKey, name, highlightIndex = -1, height = 220, yDomain }) {
    if (!data?.length || data.every((d) => d[yKey] === null || d[yKey] === undefined)) return <EmptyChart />;
    return (
        <ResponsiveContainer width="100%" height={height}>
            <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -16 }} barCategoryGap="28%">
                <CartesianGrid vertical={false} stroke={CHART.grid} />
                <XAxis dataKey={xKey} tick={{ fontSize: 12, fill: CHART.axis }} axisLine={false} tickLine={false} />
                <YAxis
                    tick={{ fontSize: 11, fill: CHART.axis }}
                    axisLine={false}
                    tickLine={false}
                    domain={yDomain}
                    allowDecimals={false}
                />
                <Tooltip cursor={{ fill: "rgba(0,0,0,0.03)" }} content={<TooltipBox />} />
                <Bar dataKey={yKey} name={name} radius={[4, 4, 0, 0]} maxBarSize={48}>
                    {data.map((_, i) => (
                        <Cell key={i} fill={i === highlightIndex ? CHART.accent : CHART.series} />
                    ))}
                </Bar>
            </BarChart>
        </ResponsiveContainer>
    );
}

/** Single-series area for a running total over time. */
export function TrendArea({ data, xKey, yKey, name, height = 220 }) {
    if (!data?.length) return <EmptyChart />;
    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -16 }}>
                <defs>
                    <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor={CHART.series} stopOpacity={0.35} />
                        <stop offset="100%" stopColor={CHART.series} stopOpacity={0.02} />
                    </linearGradient>
                </defs>
                <CartesianGrid vertical={false} stroke={CHART.grid} />
                <XAxis
                    dataKey={xKey}
                    tick={{ fontSize: 11, fill: CHART.axis }}
                    axisLine={false}
                    tickLine={false}
                    minTickGap={24}
                />
                <YAxis tick={{ fontSize: 11, fill: CHART.axis }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip content={<TooltipBox />} />
                <Area
                    type="monotone"
                    dataKey={yKey}
                    name={name}
                    stroke={CHART.series}
                    strokeWidth={2}
                    fill="url(#trendFill)"
                    dot={false}
                    activeDot={{ r: 5, strokeWidth: 2, stroke: "#fff" }}
                />
            </AreaChart>
        </ResponsiveContainer>
    );
}

/**
 * Categorical slots (validated reference palette, fixed order, never cycled).
 * Used only for part-to-whole donuts where each slice is a distinct entity.
 */
export const CATEGORICAL = ["#2a78d6", "#eb6834", "#1baf7a", "#eda100", "#e87ba4", "#008300", "#4a3aa7", "#e34948"];

/** Colors for status-like slices, keyed by label. */
export const STATUS_COLORS = {
    Submitted: CHART.series,
    Pending: CHART.muted,
    Locked: "#d97706",
    Complete: "#047857",
    "In progress": "#2a78d6",
    "Not started": "#d1d5db",
};

/**
 * Donut with a headline number in the middle and a legend beside it.
 * `data` = [{ name, value }]. Colors come from `colors` (by label) or the
 * categorical slots in order.
 */
export function Donut({ data, colors, centerValue, centerLabel, height = 220, valueFormat }) {
    const rows = (data ?? []).filter((d) => d.value > 0);
    const total = rows.reduce((s, d) => s + d.value, 0);
    if (!rows.length || total === 0) return <EmptyChart />;

    const colorFor = (d, i) => colors?.[d.name] ?? CATEGORICAL[i % CATEGORICAL.length];
    const fmt = valueFormat ?? ((v) => v);

    return (
        <div className="grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,13rem)]">
            <div className="relative min-w-0" style={{ height }}>
                <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                        <Pie
                            data={rows}
                            dataKey="value"
                            nameKey="name"
                            innerRadius="62%"
                            outerRadius="90%"
                            paddingAngle={2}
                            stroke="#fff"
                            strokeWidth={2}
                            isAnimationActive
                        >
                            {rows.map((d, i) => (
                                <Cell key={d.name} fill={colorFor(d, i)} />
                            ))}
                        </Pie>
                        <Tooltip
                            content={({ active, payload }) => {
                                if (!active || !payload?.length) return null;
                                const p = payload[0];
                                return (
                                    <div className="rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-md">
                                        <p className="font-semibold text-gray-900">{p.payload?.title ?? p.name}</p>
                                        <p className="text-gray-600">
                                            {fmt(p.value)} ({Math.round((p.value / total) * 100)}%)
                                        </p>
                                    </div>
                                );
                            }}
                        />
                    </PieChart>
                </ResponsiveContainer>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-bold tabular-nums text-gray-900">{centerValue ?? fmt(total)}</span>
                    {centerLabel && (
                        <span className="text-[11px] font-medium uppercase tracking-wide text-gray-500">{centerLabel}</span>
                    )}
                </div>
            </div>
            <ul className="min-w-0 space-y-1.5 text-sm">
                {rows.map((d, i) => (
                    <li key={d.name} className="flex min-w-0 items-center gap-2">
                        <span className="size-3 shrink-0 rounded-sm" style={{ background: colorFor(d, i) }} aria-hidden="true" />
                        <span className="min-w-0 flex-1 truncate text-gray-700" title={d.title ?? d.name}>
                            {d.name}
                        </span>
                        <span className="shrink-0 font-semibold tabular-nums text-gray-900">{fmt(d.value)}</span>
                        <span className="w-10 shrink-0 text-right text-xs tabular-nums text-gray-500">
                            {Math.round((d.value / total) * 100)}%
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** Horizontal ranked bars with the entity name on the left; first bar emphasised. */
export function RankedBars({ data, labelKey, valueKey, name, height, domain, emphasizeFirst = true }) {
    if (!data?.length) return <EmptyChart />;
    return (
        <ResponsiveContainer width="100%" height={height ?? Math.max(160, data.length * 32 + 24)}>
            <BarChart data={data} layout="vertical" margin={{ top: 4, right: 40, bottom: 4, left: 4 }} barCategoryGap={6}>
                <CartesianGrid horizontal={false} stroke={CHART.grid} />
                <XAxis type="number" hide domain={domain} />
                <YAxis
                    type="category"
                    dataKey={labelKey}
                    width={92}
                    tick={{ fontSize: 12, fill: CHART.axis }}
                    axisLine={false}
                    tickLine={false}
                />
                <Tooltip cursor={{ fill: "rgba(0,0,0,0.03)" }} content={<TooltipBox />} />
                <Bar dataKey={valueKey} name={name} radius={[0, 4, 4, 0]} maxBarSize={22} label={{ position: "right", fontSize: 11, fill: CHART.axis }}>
                    {data.map((_, i) => (
                        <Cell key={i} fill={emphasizeFirst && i === 0 ? CHART.accent : CHART.series} />
                    ))}
                </Bar>
            </BarChart>
        </ResponsiveContainer>
    );
}
