import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import {
    PieChart, Pie, Cell, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';

interface Stats {
    totalUsers: number;
    totalAppointments: number;
    totalInventory: number;
}

interface UserDistribution {
    patients: number;
    dentists: number;
    staff: number;
}

interface DashboardProps {
    stats: Stats;
    userDistribution: UserDistribution;
}

const PIE_COLORS = ['#3b82f6', '#14b8a6', '#f97316', '#a855f7'];

export default function Dashboard({ stats, userDistribution }: DashboardProps) {
    const pieData = [
        { name: 'Patients', value: userDistribution.patients },
        { name: 'Dentists', value: userDistribution.dentists },
        { name: 'Staff', value: userDistribution.staff },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Summary cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <SummaryCard
                        label="Total Users"
                        value={stats.totalUsers}
                        icon="👥"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <SummaryCard
                        label="Total Appointments"
                        value={stats.totalAppointments}
                        icon="📅"
                        accent="border-l-teal-500"
                        iconBg="bg-teal-100 dark:bg-teal-950"
                    />
                    <SummaryCard
                        label="Total Inventory"
                        value={stats.totalInventory}
                        icon="📦"
                        accent="border-l-orange-500"
                        iconBg="bg-orange-100 dark:bg-orange-950"
                    />
                </div>

                {/* User distribution */}
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h3 className="mb-3 font-semibold">User Distribution</h3>
                    <ResponsiveContainer width="100%" height={280}>
                        <PieChart>
                            <Pie
                                data={pieData}
                                dataKey="value"
                                nameKey="name"
                                cx="50%"
                                cy="50%"
                                outerRadius={95}
                                label
                            >
                                {pieData.map((_, i) => (
                                    <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />
                                ))}
                            </Pie>
                            <Legend />
                            <Tooltip />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
            </div>
        </>
    );
}

function SummaryCard({
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
            className={`flex items-start gap-3 rounded-xl border border-l-4 ${accent} border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900`}
        >
            <div className={`flex size-10 items-center justify-center rounded-full text-lg ${iconBg}`}>
                {icon}
            </div>
            <div>
                <p className="text-xs text-neutral-500">{label}</p>
                <h2 className="text-2xl font-semibold">{value}</h2>
            </div>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};