import { useEffect, useState } from "react";
import { router, usePage } from "@inertiajs/react";
import { toast } from "sonner";

import { AppSidebar } from "@/Components/AppSidebar";
import Breadcrumbs from "@/Components/Breadcrumbs";
import { Separator } from "@/Components/ui/separator";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/Components/ui/sidebar";
import { Toaster } from "@/Components/ui/sonner";

/**
 * Authenticated shell used by both roles. The sidebar adapts to the
 * signed-in user's role. Flash messages from the server become toasts.
 *
 * Pass `breadcrumbs` ([{ label, href? }, ...]) to show a trail in the header;
 * `title` is used when no breadcrumbs are given.
 */
export default function AppLayout({ title, breadcrumbs, actions, children }) {
    const { flash, component } = usePage().props;
    const pageComponent = usePage().component;
    const [navigating, setNavigating] = useState(false);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    // Soften the page while a full navigation is in flight (visible on slow connections).
    useEffect(() => {
        const offStart = router.on("start", (event) => {
            const isPartial = event.detail.visit.only?.length > 0;
            if (!isPartial) setNavigating(true);
        });
        const offFinish = router.on("finish", () => setNavigating(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset className="min-h-screen bg-gray-50">
                <header className="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-2 border-b bg-white/90 px-4 shadow-sm backdrop-blur print:hidden">
                    <SidebarTrigger className="-ml-1" />
                    <Separator orientation="vertical" className="mr-2 h-4" />
                    {breadcrumbs?.length ? (
                        <Breadcrumbs items={breadcrumbs} />
                    ) : (
                        <h1 className="truncate text-base font-semibold text-gray-800 sm:text-lg">{title}</h1>
                    )}
                    {actions && <div className="ml-auto flex shrink-0 items-center gap-2">{actions}</div>}
                </header>
                <main aria-busy={navigating || undefined} className="flex-1 p-4 sm:p-6 print:p-0">
                    {/* Keyed by page component so each new page fades up, but in-page URL changes do not. */}
                    <div key={pageComponent ?? component} className="page-enter">
                        {children}
                    </div>
                </main>
            </SidebarInset>
            <Toaster
                theme="light"
                position="top-right"
                offset={{ top: 64, right: 16 }}
                mobileOffset={{ top: 64, left: 12, right: 12 }}
                richColors
                closeButton
                expand={false}
                visibleToasts={4}
            />
        </SidebarProvider>
    );
}
