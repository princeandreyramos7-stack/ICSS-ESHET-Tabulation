import { Card, CardContent } from "@/Components/ui/card";

export default function StatCard({ label, value, hint, icon: Icon, tone = "emerald" }) {
    const tones = {
        emerald: "bg-emerald-100 text-emerald-800",
        amber: "bg-amber-100 text-amber-800",
        blue: "bg-sky-100 text-sky-800",
        gray: "bg-gray-100 text-gray-700",
    };

    return (
        <Card className="lift shadow-sm">
            <CardContent className="flex items-center gap-4 p-4 sm:p-5">
                {Icon && (
                    <div className={`flex size-11 shrink-0 items-center justify-center rounded-lg ${tones[tone]}`}>
                        <Icon className="size-5" />
                    </div>
                )}
                <div className="min-w-0">
                    <p className="truncate text-xs font-medium uppercase tracking-wide text-gray-500">
                        {label}
                    </p>
                    <p className="text-2xl font-bold leading-tight text-gray-900">{value}</p>
                    {hint && <p className="truncate text-xs text-gray-500">{hint}</p>}
                </div>
            </CardContent>
        </Card>
    );
}
