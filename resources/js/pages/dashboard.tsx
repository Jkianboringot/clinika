import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import {
    LineChart, Line, PieChart, Pie, Cell,
    XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';
import LowStockAlert, { type LowStockItem } from '@/components/low-stock-alert';

interface Stats {
    totalUsers: number;
    userGrowth: number;
}

interface UserDistribution {
    patients: number;
    dentists: number;
    staff: number;
    admins: number;
}

interface SystemStats {
    activePatients: number;
    dentists: number;
    staffMembers: number;
    admins: number;
    totalUsers: number;
}

interface TrendPoint {
    month: string;
    users: number;
}

interface DashboardProps {
    stats: Stats;
    userDistribution: UserDistribution;
    systemStats: SystemStats;
    userTrends: TrendPoint[];
    lowStockItems?: LowStockItem[];
}

const PIE_COLORS = ['#3b82f6', '#14b8a6', '#f97316', '#a855f7'];

export default function Dashboard({
    stats,
    userDistribution,
    systemStats,
    userTrends,
    lowStockItems = [],
}: DashboardProps) {
    const pieData = [
        { name: 'Patients', value: userDistribution.patients },
        { name: 'Dentists', value: userDistribution.dentists },
        { name: 'Staff', value: userDistribution.staff },
        { name: 'Admins', value: userDistribution.admins },
    ];

    const isPositive = stats.userGrowth >= 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Low stock alert: items below their minimum stock */}
                <LowStockAlert items={lowStockItems} showLink />

                {/* Summary card */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="flex items-start gap-3 rounded-xl border border-l-4 border-l-blue-500 border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="flex size-10 items-center justify-center rounded-full bg-blue-100 text-lg dark:bg-blue-950">
                            👥
                        </div>
                        <div>
                            <p className="text-xs text-neutral-500">Total Users</p>
                            <h2 className="text-2xl font-semibold">{stats.totalUsers}</h2>
                            <span className={`text-xs ${isPositive ? 'text-green-600' : 'text-red-500'}`}>
                                {isPositive ? '+' : ''}
                                {stats.userGrowth}% this month
                            </span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border md:col-span-2">
                        <h3 className="mb-3 font-semibold">User Signups (Last 5 Months)</h3>
                        <ResponsiveContainer width="100%" height={180}>
                            <LineChart data={userTrends}>
                                <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
                                <XAxis dataKey="month" />
                                <YAxis allowDecimals={false} />
                                <Tooltip />
                                <Line
                                    type="monotone"
                                    dataKey="users"
                                    stroke="#3b82f6"
                                    strokeWidth={2}
                                    dot={{ r: 4 }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Distribution + stats */}
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="mb-3 font-semibold">User Distribution</h3>
                        <ResponsiveContainer width="100%" height={240}>
                            <PieChart>
                                <Pie
                                    data={pieData}
                                    dataKey="value"
                                    nameKey="name"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={85}
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

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="mb-3 font-semibold">System Statistics</h3>
                        <ul className="divide-y divide-sidebar-border/50 text-sm">
                            <StatRow label="Active Patients" value={systemStats.activePatients} />
                            <StatRow label="Dentists" value={systemStats.dentists} />
                            <StatRow label="Staff Members" value={systemStats.staffMembers} />
                            <StatRow label="Admins" value={systemStats.admins} />
                            <StatRow label="Total Users" value={systemStats.totalUsers} />
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}

function StatRow({ label, value }: { label: string; value: string | number }) {
    return (
        <li className="flex items-center justify-between py-2">
            <span className="text-neutral-500">{label}</span>
            <strong>{value}</strong>
        </li>
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