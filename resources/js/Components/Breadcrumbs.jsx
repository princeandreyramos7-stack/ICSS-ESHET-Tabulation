import { Link } from "@inertiajs/react";
import { ChevronRight, Home } from "lucide-react";

/**
 * Header breadcrumb trail. Each item: { label, href? }.
 * The last item is the current page and is not a link.
 */
export default function Breadcrumbs({ items = [] }) {
    if (!items.length) return null;

    return (
        <nav aria-label="Breadcrumb" className="min-w-0">
            <ol className="flex min-w-0 items-center gap-1 text-sm">
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    return (
                        <li key={`${item.label}-${index}`} className="flex min-w-0 items-center gap-1">
                            {index > 0 && (
                                <ChevronRight className="size-4 shrink-0 text-gray-400" aria-hidden="true" />
                            )}
                            {isLast || !item.href ? (
                                <span
                                    aria-current={isLast ? "page" : undefined}
                                    className={`truncate ${
                                        isLast ? "font-semibold text-gray-900" : "text-gray-500"
                                    }`}
                                    title={item.label}
                                >
                                    {index === 0 && item.icon !== false && (
                                        <Home className="mr-1 inline size-3.5 -translate-y-px text-gray-400" />
                                    )}
                                    {item.label}
                                </span>
                            ) : (
                                <Link
                                    href={item.href}
                                    className="truncate text-gray-500 transition hover:text-emerald-800 hover:underline"
                                    title={item.label}
                                >
                                    {index === 0 && item.icon !== false && (
                                        <Home className="mr-1 inline size-3.5 -translate-y-px text-gray-400" />
                                    )}
                                    {item.label}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
