import { Link, usePage } from "@inertiajs/react";
import {
    ClipboardList,
    FileText,
    LayoutDashboard,
    Lock,
    Printer,
    Trophy,
    Users,
} from "lucide-react";

import ConferenceLogo from "@/Components/Brand/ConferenceLogo";
import { NavUser } from "@/Components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from "@/Components/ui/sidebar";

/** Small numbered badge so tracks stay distinguishable when the sidebar is collapsed. */
function TrackBadge({ number, active }) {
    return (
        <span
            aria-hidden="true"
            className={`flex size-4 shrink-0 items-center justify-center rounded text-[10px] font-bold leading-none ${
                active ? "bg-emerald-950 text-amber-300" : "bg-white/15 text-emerald-50"
            }`}
        >
            {number}
        </span>
    );
}

function NavGroup({ label, items }) {
    if (!items.length) return null;
    return (
        <SidebarGroup>
            <SidebarGroupLabel className="text-amber-300">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.href}>
                        <SidebarMenuButton
                            asChild
                            tooltip={item.title}
                            className={`w-full rounded-md px-3 py-2 transition hover:bg-white/15 hover:text-white ${
                                item.isActive
                                    ? "bg-amber-400 text-emerald-950 hover:bg-amber-300 hover:text-emerald-950"
                                    : "text-emerald-50"
                            }`}
                        >
                            <Link href={item.href}>
                                {item.badge !== undefined ? (
                                    <TrackBadge number={item.badge} active={item.isActive} />
                                ) : (
                                    <item.icon />
                                )}
                                <span className="truncate">{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

export function AppSidebar(props) {
    const { auth, tracks = [], conference } = usePage().props;
    const currentUrl = usePage().url;
    const user = auth.user;
    const isAdmin = user?.role === "admin";

    const isActive = (href, exact = false) => {
        const path = href.replace(/^https?:\/\/[^/]+/, "");
        return exact ? currentUrl === path : currentUrl.startsWith(path);
    };
    // The evaluator workspace is one page; the active track lives in ?track=.
    const currentTrackParam = new URLSearchParams(currentUrl.split("?")[1] ?? "").get("track");

    const menu = isAdmin
        ? [
              {
                  title: "Dashboard",
                  href: route("admin.dashboard"),
                  icon: LayoutDashboard,
                  isActive: isActive(route("admin.dashboard"), true),
              },
              {
                  title: "Papers",
                  href: route("admin.papers.index"),
                  icon: FileText,
                  isActive: isActive(route("admin.papers.index")),
              },
              {
                  title: "Evaluators",
                  href: route("admin.evaluators.index"),
                  icon: Users,
                  isActive: isActive(route("admin.evaluators.index")),
              },
              {
                  title: "Tracks & Locks",
                  href: route("admin.tracks.index"),
                  icon: Lock,
                  isActive: isActive(route("admin.tracks.index")),
              },
              {
                  title: "Overall Results",
                  href: route("admin.results.overall"),
                  icon: Trophy,
                  isActive: isActive(route("admin.results.overall")),
              },
          ]
        : [
              {
                  title: "Dashboard",
                  href: route("evaluator.dashboard"),
                  icon: LayoutDashboard,
                  isActive: isActive(route("evaluator.dashboard"), true),
              },
          ];

    const trackItems = tracks.map((track) => {
        const href = isAdmin
            ? route("admin.results.track", track.id)
            : route("evaluator.workspace", { track: track.id });
        return {
            title: `Track ${track.number}: ${track.name}`,
            href,
            icon: isAdmin ? Printer : ClipboardList,
            badge: track.number,
            isActive: isAdmin ? isActive(href) : currentTrackParam === String(track.id),
        };
    });

    return (
        <Sidebar collapsible="icon" className="print:hidden" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            tooltip={conference?.short_name}
                            className="bg-amber-400 text-emerald-950 hover:bg-amber-300 hover:text-emerald-950 group-data-[collapsible=icon]:justify-center"
                        >
                            <Link href={route("dashboard")}>
                                <ConferenceLogo className="size-8" ring={false} />
                                <span className="grid flex-1 text-left leading-tight">
                                    <span className="truncate text-sm font-bold">{conference?.short_name}</span>
                                    <span className="truncate text-[11px] text-emerald-900">
                                        {isAdmin ? "Administrator" : "Panel of Evaluators"}
                                    </span>
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                <NavGroup label="Menu" items={menu} />
                <NavGroup
                    label={isAdmin ? "Results by Track" : "Evaluate by Track"}
                    items={trackItems}
                />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
