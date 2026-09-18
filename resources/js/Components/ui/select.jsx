import * as React from "react";

import { cn } from "@/lib/utils";

/** Native select styled to match the shadcn Input. Reliable on every mobile browser. */
const Select = React.forwardRef(({ className, children, ...props }, ref) => {
    return (
        <select
            className={cn(
                "flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-base shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
                className
            )}
            ref={ref}
            {...props}
        >
            {children}
        </select>
    );
});
Select.displayName = "Select";

export { Select };
