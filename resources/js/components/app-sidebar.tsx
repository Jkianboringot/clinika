import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Syringe, Pill, ListPlus, UsersRound, FileUser, TicketCheck, Stethoscope, PackagePlus, FileChartColumn } from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { useCan } from '@/hooks/use-can';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

// `permission` = the permission(s) the user needs to see this link.
// An array means "any one of these is enough".
type PermissionNavItem = NavItem & { permission: string | string[] };

export function AppSidebar() {
    const { auth } = usePage<{ auth: { guard: 'web' | 'patient' | null } }>().props;
    const can = useCan();

    const isPatient = auth?.guard === 'patient';

    const mainNavItems: PermissionNavItem[] = [
        // ---------- Staff / Admin / Dentist (web guard) ----------
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            permission: ['admin_dashboard.view', 'staff_dashboard.view', 'dentist_dashboard.view'],
        },
        { title: 'Procedures', href: '/procedures', icon: Syringe, permission: 'procedures.view' },
        { title: 'Medication', href: '/medications', icon: Pill, permission: 'medications.view' },
        { title: 'Items', href: '/inventory_items', icon: ListPlus, permission: 'inventory.view' },
        { title: 'Patient', href: '/patients', icon: UsersRound, permission: 'patients.view' },
        { title: 'Appointment', href: '/appointments', icon: TicketCheck, permission: 'appointments.view' },
        { title: 'Treatment', href: '/treatments', icon: Stethoscope, permission: 'treatments.view' },
        { title: 'Inventory', href: '/inventories', icon: PackagePlus, permission: 'inventory.view' },
        { title: 'User', href: '/users', icon: FileUser, permission: 'users.view' },
        { title: 'Report', href: '/reports', icon: FileChartColumn, permission: 'reports.view' },

        // ---------- Patient (patient guard) ----------
        { title: 'Dashboard', href: '/patient/dashboard', icon: LayoutGrid, permission: 'patient_dashboard.view' },
        { title: 'My Appointments', href: '/patient/appointments', icon: TicketCheck, permission: 'appointments.view_own' },
        { title: 'My Treatments', href: '/patient/treatments', icon: Stethoscope, permission: 'treatments.view_own' },
    ];

    const visibleNavItems = mainNavItems.filter((item) => {
        const required = Array.isArray(item.permission) ? item.permission : [item.permission];

        return required.some((p) => can(p));
    });

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={isPatient ? '/patient/dashboard' : dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}