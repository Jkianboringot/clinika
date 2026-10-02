import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface Stats {
    todayAppointments: number;
    totalPatientsTreated: number;
    pendingFollowUps: number;
}

interface DentistDashboardProps {
    dentist: { name: string };
    today: string;
    stats: Stats;
}

export default function DentistDashboard({
    dentist,
    today,
    stats,
}: DentistDashboardProps) {
    return (
        <>
            <Head title="Dentist Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold">Welcome back, Dr. {dentist.name}!</h1>
                    <p className="text-sm text-neutral-500">Here's your overview for today, {today}</p>
                </div>

                {/* Stat cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <StatCard
                        label="Today's Appointments"
                        value={stats.todayAppointments}
                        icon="📅"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <StatCard
                        label="Total Patients"
                        value={stats.totalPatientsTreated}
                        icon="👥"
                        accent="border-l-teal-500"
                        iconBg="bg-teal-100 dark:bg-teal-950"
                    />
                    <StatCard
                        label="Pending Follow-ups"
                        value={stats.pendingFollowUps}
                        icon="⏰"
                        accent="border-l-orange-500"
                        iconBg="bg-orange-100 dark:bg-orange-950"
                    />
                </div>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    icon,
    accent,
    iconBg,
}: {
    label: string;
    value: string | number;
    icon: string;
    accent: string;
    iconBg: string;
}) {
    return (
        <div
            className={`flex items-center justify-between rounded-xl border border-l-4 ${accent} border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900`}
        >
            <div>
                <p className="text-xs text-neutral-500">{label}</p>
                <h2 className="text-3xl font-bold">{value}</h2>
            </div>
            <div className={`flex size-10 items-center justify-center rounded-full text-lg ${iconBg}`}>
                {icon}
            </div>
        </div>
    );
}

DentistDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};