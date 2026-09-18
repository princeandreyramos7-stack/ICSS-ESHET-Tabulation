import { Loader2 } from "lucide-react";

import { cn } from "@/lib/utils";

/** Inline spinner. Sized by className (defaults to size-4 to match button icons). */
export function Spinner({ className, ...props }) {
    return (
        <Loader2
            aria-hidden="true"
            className={cn("size-4 shrink-0 animate-spin", className)}
            {...props}
        />
    );
}
