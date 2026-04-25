import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookOpenCheck,
    Brain,
    ClipboardList,
    FileText,
    LayoutGrid,
    LibraryBig,
    Mic,
    Route,
    Settings,
    ShieldCheck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as analyticsIndex } from '@/routes/analytics';
import { setup as examSetup } from '@/routes/exam';
import { index as mistakesIndex } from '@/routes/mistakes';
import { setup as practiceSetup } from '@/routes/practice';
import { edit as profileEdit } from '@/routes/profile';
import { index as speakingIndex } from '@/routes/speaking';
import { index as studyPathIndex } from '@/routes/study-path';
import { index as vocabularyIndex } from '@/routes/vocabulary';
import { index as writingIndex } from '@/routes/writing';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Study Path',
        href: studyPathIndex(),
        icon: Route,
    },
    {
        title: 'Practice',
        href: practiceSetup(),
        icon: ClipboardList,
    },
    {
        title: 'Exam',
        href: examSetup(),
        icon: ShieldCheck,
    },
    {
        title: 'Vocabulary',
        href: vocabularyIndex(),
        icon: LibraryBig,
    },
    {
        title: 'Speaking',
        href: speakingIndex(),
        icon: Mic,
    },
    {
        title: 'Writing',
        href: writingIndex(),
        icon: FileText,
    },
    {
        title: 'Mistakes',
        href: mistakesIndex(),
        icon: Brain,
    },
    {
        title: 'Analytics',
        href: analyticsIndex(),
        icon: BarChart3,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isAdmin = auth.user?.role === 'admin';

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            className="border-r border-violet-100 bg-sidebar dark:border-indigo-950"
        >
            <SidebarHeader className="px-5 py-6">
                <SidebarMenu>
                    {isAdmin && (
                        <SidebarMenuItem>
                            <SidebarMenuButton asChild tooltip="Admin">
                                <Link href="/admin">
                                    <ShieldCheck />
                                    <span>Admin</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    )}
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="gap-3 px-5 py-5">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip="Settings">
                            <Link href={profileEdit()}>
                                <Settings />
                                <span>Settings</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton asChild tooltip="TOEFL ITP">
                            <Link href={studyPathIndex()}>
                                <BookOpenCheck />
                                <span>TOEFL ITP</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <Button
                    asChild
                    className="h-10 w-full bg-primary shadow-sm shadow-indigo-500/25 group-data-[collapsible=icon]/sidebar-wrapper:size-9 group-data-[collapsible=icon]/sidebar-wrapper:px-0 hover:bg-primary/90"
                >
                    <Link href={practiceSetup()}>
                        <ClipboardList className="size-4" />
                        <span className="group-data-[collapsible=icon]/sidebar-wrapper:hidden">
                            Start Practice
                        </span>
                    </Link>
                </Button>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
