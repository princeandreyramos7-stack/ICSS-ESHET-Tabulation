import { ChevronsUpDown, LogOut, UserCog } from "lucide-react";
import { Link, router } from "@inertiajs/react";

import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from "@/Components/ui/sidebar";
import { useInitials } from "@/hooks/use-initials";

export function NavUser({ user }) {
    const { isMobile } = useSidebar();
    const getInitials = useInitials();

    if (!user) return null;

    const handleLogout = () => router.post(route("logout"));

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={user.name}
                            className="border border-amber-300/60 text-emerald-50 data-[state=open]:bg-white/15 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:border-0"
                        >
                            <Avatar className="size-8 shrink-0 rounded-lg">
                                <AvatarFallback className="rounded-lg bg-amber-400 text-emerald-950">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">{user.name}</span>
                                <span className="truncate text-xs text-emerald-100">{user.email}</span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                        side={isMobile ? "bottom" : "right"}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="px-2 py-1.5 text-left text-sm">
                                <p className="truncate font-semibold">{user.name}</p>
                                <p className="truncate text-xs capitalize text-gray-500">{user.role}</p>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild className="cursor-pointer">
                            <Link href={route("profile.edit")}>
                                <UserCog className="mr-2 size-4" />
                                My account
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="cursor-pointer" onSelect={handleLogout}>
                            <LogOut className="mr-2 size-4" />
                            Log out
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
