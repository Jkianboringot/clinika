import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, House, Syringe, Pill, ListPlus, UsersRound, FileUser, TicketCheck, Stethoscope } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
// import { useCan } from '@/hooks/use-can';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

// --- PERMISSION-BASED NAV (disabled for now) ---------------------------------
// To enable:
//   1. uncomment the useCan import above, the type below, `const can`, and `visibleNavItems`
//   2. change `const mainNavItems: NavItem[]` to `const mainNavItems: PermissionNavItem[]`
//   3. uncomment every `permission:` line in the items
//   4. replace the `...(canManageUsers ? [...] : [])` block with the plain "User" item below
//   5. pass `visibleNavItems` to <NavMain />
//
// `permission` = the permission the user needs to see this link
// type PermissionNavItem = NavItem & { permission: string };

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Repository',
//         href: 'https://github.com/laravel/react-starter-kit',
//         icon: FolderGit2,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#react',
//         icon: BookOpen,
//     },
// ];

export function AppSidebar() {
    const { permission } = usePage<{ permission?: { canManageUsers?: boolean } }>().props;
    const canManageUsers = permission?.canManageUsers ?? false;
    // const can = useCan();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            // permission: 'dashboard.view',
        },
        {
            title: 'Procedures',
            href: '/procedures',
            icon: Syringe,
            // permission: 'procedures.view',
        },
        {
            title: 'Medication',
            href: '/medications',
            icon: Pill,
            // permission: 'medications.view',
        },
        {
            title: 'Items',
            href: '/inventory_items',
            icon: ListPlus,
            // permission: 'inventory.view',
        },
        {
            title: 'Patient',
            href: '/patients',
            icon: UsersRound,
            // permission: 'patients.view',
        },
        {
            title: 'Appointment',
            href: '/appointments',
            icon: TicketCheck,
            // permission: 'appointments.view',
        },
        {
            title: 'Treatment',
            href: '/treatments',
            icon: Stethoscope,
            // permission: 'treatments.view',
        },
        {
            title: 'Inventory',
            href: '/inventories',
            icon: Stethoscope,
            // permission: 'inventory.view',
        },
        ...(canManageUsers
            ? [
                {
                    title: 'User',
                    href: '/users',
                    icon: FileUser,
                },
            ]
            : []),
        // Replacement for the block above when permission-based nav is enabled:
        // {
        //     title: 'User',
        //     href: '/users',
        //     icon: FileUser,
        //     permission: 'users.route',
        // },
    ];

    // const visibleNavItems = mainNavItems.filter((item) => can(item.permission));

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
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
                {/* <NavMain items={visibleNavItems} /> */}
            </SidebarContent>

            <SidebarFooter>
                {/* <NavFooter items={footerNavItems} className="mt-auto" /> */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}