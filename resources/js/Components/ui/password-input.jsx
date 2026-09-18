import * as React from "react";
import { Eye, EyeOff } from "lucide-react";

import { Input } from "@/Components/ui/input";
import { cn } from "@/lib/utils";

/**
 * Password field with a show/hide toggle. The toggle is a real button with an
 * accessible label and never submits the form.
 */
const PasswordInput = React.forwardRef(({ className, ...props }, ref) => {
    const [visible, setVisible] = React.useState(false);

    return (
        <div className="relative">
            <Input
                ref={ref}
                type={visible ? "text" : "password"}
                className={cn("pr-10", className)}
                {...props}
            />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                aria-label={visible ? "Hide password" : "Show password"}
                aria-pressed={visible}
                title={visible ? "Hide password" : "Show password"}
                tabIndex={-1}
                className="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-md text-gray-400 transition hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
});
PasswordInput.displayName = "PasswordInput";

export { PasswordInput };
