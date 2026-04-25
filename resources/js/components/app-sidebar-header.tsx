import { Link, usePage } from '@inertiajs/react';
import { Bell, Search, Settings } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';
import { edit as profileEdit } from '@/routes/profile';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-violet-100/80 bg-white/90 px-4 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6 dark:border-indigo-950 dark:bg-slate-950/85">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <div className="hidden text-sm text-slate-500 lg:block">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <div className="relative ml-1 hidden w-72 max-w-[28vw] items-center lg:flex">
                    <Search className="absolute left-3 size-4 text-slate-400" />
                    <input
                        type="search"
                        placeholder="Search lessons, vocab..."
                        className="h-9 w-full rounded-full border border-violet-100 bg-violet-50/80 pr-4 pl-9 text-sm transition outline-none focus:border-indigo-300 focus:bg-white focus:ring-3 focus:ring-indigo-100 dark:border-indigo-900 dark:bg-slate-900 dark:focus:ring-indigo-950"
                    />
                </div>
            </div>
            <div className="ml-auto flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-9 rounded-full text-slate-700 hover:bg-violet-100 dark:text-slate-200 dark:hover:bg-indigo-950"
                >
                    <Bell className="size-4" />
                    <span className="sr-only">Notifications</span>
                </Button>
                <Button
                    asChild
                    variant="ghost"
                    size="icon"
                    className="size-9 rounded-full text-slate-700 hover:bg-violet-100 dark:text-slate-200 dark:hover:bg-indigo-950"
                >
                    <Link href={profileEdit()}>
                        <Settings className="size-4" />
                        <span className="sr-only">Settings</span>
                    </Link>
                </Button>
                <Avatar className="size-9 rounded-full bg-violet-100">
                    <AvatarImage
                        src={auth.user?.avatar}
                        alt={auth.user?.name}
                    />
                    <AvatarFallback className="rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200">
                        {getInitials(auth.user?.name ?? 'User')}
                    </AvatarFallback>
                </Avatar>
            </div>
        </header>
    );
}
