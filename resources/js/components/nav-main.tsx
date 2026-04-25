import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-5 py-0 group-data-[collapsible=icon]/sidebar-wrapper:px-2">
            <SidebarGroupLabel className="px-0 text-[0.7rem] font-semibold tracking-[0.18em] text-sidebar-foreground/45 uppercase">
                Learning
            </SidebarGroupLabel>
            <SidebarMenu className="gap-2">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            className="h-11 rounded-lg px-3 text-[0.95rem] data-[active=true]:bg-primary data-[active=true]:text-primary-foreground data-[active=true]:shadow-sm data-[active=true]:shadow-indigo-500/25"
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
