import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props as any;
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-40 [overscroll-behavior-y:none] flex h-16 shrink-0 items-center justify-between gap-2 border-b-2 border-brand-orange bg-white px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                {breadcrumbs.length > 0 ? (
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                ) : (
                    <span className="text-lg font-bold text-ink">Admin Panel</span>
                )}
            </div>

            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#3a2416] text-xs font-bold text-white">
                {getInitials(auth.user?.name ?? '')}
            </div>
        </header>
    );
}