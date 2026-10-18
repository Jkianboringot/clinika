import { Head, Link } from '@inertiajs/react';
import { dashboard } from '@/routes';
import {
    PieChart, Pie, Cell, Tooltip, ResponsiveContainer, Legend,
    BarChart, Bar, XAxis, YAxis, CartesianGrid,
} from 'recharts';

interface Stats {
    totalUsers: number;
    totalPatients: number;
    totalAppointments: number;
    totalInventory: number;
    todayAppointments: number;
    pendingAppointments: number;
    lowStockCount: number;
    newPatientsThisMonth: number;
    appointmentsThisMonth: number;
    appointmentsLastMonth: number;
}

interface UserDistribution {
    patients: number;
    dentists: number;
    staff: number;
}

interface TrendPoint {
    label: string;
    total: number;
}

interface StatusPoint {
    status: string;
    total: number;
}

interface RecentAppointment {
    id: number;
    patient: string;
    staff: string;
    procedure: string;
    date: string;
    time: string;
    status: string;
}

interface LowStockItem {
    id: number;
    name: string;
    current: number;
    minimum: number;
    percent: number;
    critical: boolean;
}

interface DashboardProps {
    admin: { name: string };
    today: string;
    stats: Stats;
    userDistribution: UserDistribution;
    appointmentTrend: TrendPoint[];
    appointmentsByStatus: StatusPoint[];
    recentAppointments: RecentAppointment[];
    lowStockItems: LowStockItem[];
}

const PIE_COLORS = ['#3b82f6', '#14b8a6', '#f97316', '#a855f7'];

const STATUS_COLORS: Record<string, string> = {
    pending: '#737373',
    scheduled: '#3b82f6',
    confirmed: '#14b8a6',
    in_progress: '#a855f7',
    completed: '#22c55e',
    cancelled: '#ef4444',
};

const STATUS_BADGE: Record<string, string> = {
    pending: 'bg-neutral-600 text-white',
    scheduled: 'bg-blue-600 text-white',
    confirmed: 'bg-teal-600 text-white',
    in_progress: 'bg-purple-600 text-white',
    completed: 'bg-green-600 text-white',
    cancelled: 'bg-red-600 text-white',
};

const normalize = (s: string) => s.toLowerCase().replace(/\s+/g, '_');

const statusLabel = (s: string) =>
    s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const statusBadge = (s: string) => STATUS_BADGE[normalize(s)] ?? 'bg-neutral-600 text-white';

const statusColor = (s: string) => STATUS_COLORS[normalize(s)] ?? '#a3a3a3';

function monthChange(current: number, previous: number): string {
    if (previous === 0) {
        return current === 0 ? 'No change vs last month' : `${current} this month`;
    }
    const pct = Math.round(((current - previous) / previous) * 100);
    const sign = pct > 0 ? '+' : '';
    return `${sign}${pct}% vs last month`;
}

export default function Dashboard({
    admin,
    today,
    stats,
    userDistribution,
    appointmentTrend,
    appointmentsByStatus,
    recentAppointments,
    lowStockItems,
}: DashboardProps) {
    const pieData = [
        { name: 'Patients', value: userDistribution.patients },
        { name: 'Dentists', value: userDistribution.dentists },
        { name: 'Staff', value: userDistribution.staff },
    ];

    const statusData = appointmentsByStatus.map((s) => ({
        name: statusLabel(s.status),
        value: s.total,
        color: statusColor(s.status),
    }));

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold">Welcome back, {admin.name}!</h1>
                    <p className="text-sm text-neutral-500">Here's the clinic overview for {today}</p>
                </div>

                {/* Summary cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <SummaryCard
                        label="Today's Appointments"
                        value={stats.todayAppointments}
                        icon="🗓️"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <SummaryCard
                        label="Pending Appointments"
                        value={stats.pendingAppointments}
                        icon="⏳"
                        accent="border-l-neutral-500"
                        iconBg="bg-neutral-100 dark:bg-neutral-800"
                        hint="Awaiting confirmation"
                    />
                    <SummaryCard
                        label="Low Stock Items"
                        value={stats.lowStockCount}
                        icon="⚠️"
                        accent="border-l-red-500"
                        iconBg="bg-red-100 dark:bg-red-950"
                        hint={stats.lowStockCount > 0 ? 'Needs restocking' : 'All stocked up'}
                        alert={stats.lowStockCount > 0}
                    />
                    <SummaryCard
                        label="New Patients (This Month)"
                        value={stats.newPatientsThisMonth}
                        icon="🧑‍⚕️"
                        accent="border-l-green-500"
                        iconBg="bg-green-100 dark:bg-green-950"
                    />
                    <SummaryCard
                        label="Total Users"
                        value={stats.totalUsers}
                        icon="👥"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <SummaryCard
                        label="Total Patients"
                        value={stats.totalPatients}
                        icon="🦷"
                        accent="border-l-purple-500"
                        iconBg="bg-purple-100 dark:bg-purple-950"
                    />
                    <SummaryCard
                        label="Total Appointments"
                        value={stats.totalAppointments}
                        icon="📅"
                        accent="border-l-teal-500"
                        iconBg="bg-teal-100 dark:bg-teal-950"
                        hint={monthChange(stats.appointmentsThisMonth, stats.appointmentsLastMonth)}
                    />
                    <SummaryCard
                        label="Total Inventory"
                        value={stats.totalInventory}
                        icon="📦"
                        accent="border-l-orange-500"
                        iconBg="bg-orange-100 dark:bg-orange-950"
                    />
                </div>

                {/* Charts row */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel title="Appointments (Last 7 Days)" className="lg:col-span-2">
                        <ResponsiveContainer width="100%" height={280}>
                            <BarChart data={appointmentTrend}>
                                <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
                                <XAxis dataKey="label" fontSize={12} />
                                <YAxis allowDecimals={false} fontSize={12} />
                                <Tooltip />
                                <Bar dataKey="total" name="Appointments" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </Panel>

                    <Panel title="Appointments by Status">
                        {statusData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={280}>
                                <PieChart>
                                    <Pie
                                        data={statusData}
                                        dataKey="value"
                                        nameKey="name"
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={55}
                                        outerRadius={90}
                                        paddingAngle={2}
                                    >
                                        {statusData.map((d, i) => (
                                            <Cell key={i} fill={d.color} />
                                        ))}
                                    </Pie>
                                    <Legend />
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <EmptyState text="No appointments yet." />
                        )}
                    </Panel>
                </div>

                {/* User distribution + low stock */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel title="User Distribution">
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
                    </Panel>

                    <Panel
                        title="Low Stock Alerts"
                        action={{ label: 'View inventory', href: '/inventory' }}
                    >
                        {lowStockItems.length > 0 ? (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {lowStockItems.map((item) => (
                                    <li key={item.id} className="py-3">
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">{item.name}</span>
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-semibold text-white ${
                                                    item.critical ? 'bg-red-600' : 'bg-orange-500'
                                                }`}
                                            >
                                                {item.current <= 0 ? 'Out of stock' : item.critical ? 'Critical' : 'Low'}
                                            </span>
                                        </div>
                                        <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                            <div
                                                className={`h-full rounded-full ${item.critical ? 'bg-red-500' : 'bg-orange-500'}`}
                                                style={{ width: `${item.percent}%` }}
                                            />
                                        </div>
                                        <p className="mt-1 text-xs text-neutral-500">
                                            {item.current} left · minimum {item.minimum}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState text="Nothing is running low. 🎉" />
                        )}
                    </Panel>
                </div>

                {/* Recent appointments */}
                <Panel
                    title="Recent Appointments"
                    action={{ label: 'View all', href: '/appointments' }}
                >
                    {recentAppointments.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[640px] text-left text-sm">
                                <thead className="text-xs uppercase text-neutral-500">
                                    <tr>
                                        <th className="pb-2 font-medium">Patient</th>
                                        <th className="pb-2 font-medium">Staff</th>
                                        <th className="pb-2 font-medium">Procedure</th>
                                        <th className="pb-2 font-medium">Date & Time</th>
                                        <th className="pb-2 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {recentAppointments.map((a) => (
                                        <tr key={a.id}>
                                            <td className="py-3 font-medium">{a.patient}</td>
                                            <td className="py-3">{a.staff}</td>
                                            <td className="py-3">{a.procedure}</td>
                                            <td className="py-3">
                                                {a.date}
                                                <span className="text-neutral-500"> · {a.time}</span>
                                            </td>
                                            <td className="py-3">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusBadge(a.status)}`}
                                                >
                                                    {statusLabel(a.status)}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <EmptyState text="No appointments to show." />
                    )}
                </Panel>
            </div>
        </>
    );
}

function Panel({
    title,
    children,
    className = '',
    action,
}: {
    title: string;
    children: React.ReactNode;
    className?: string;
    action?: { label: string; href: string };
}) {
    return (
        <div className={`rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border ${className}`}>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold">{title}</h3>
                {action && (
                    <Link href={action.href} className="text-xs font-semibold text-blue-600 hover:underline">
                        {action.label} →
                    </Link>
                )}
            </div>
            {children}
        </div>
    );
}

function EmptyState({ text }: { text: string }) {
    return <p className="py-10 text-center text-sm text-neutral-500">{text}</p>;
}

function SummaryCard({
    label,
    value,
    icon,
    accent,
    iconBg,
    hint,
    alert = false,
}: {
    label: string;
    value: string | number;
    icon: string;
    accent: string;
    iconBg: string;
    hint?: string;
    alert?: boolean;
}) {
    return (
        <div
            className={`flex items-start gap-3 rounded-xl border border-l-4 ${accent} border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900`}
        >
            <div className={`flex size-10 shrink-0 items-center justify-center rounded-full text-lg ${iconBg}`}>
                {icon}
            </div>
            <div>
                <p className="text-xs text-neutral-500">{label}</p>
                <h2 className="text-2xl font-semibold">{value}</h2>
                {hint && (
                    <p className={`mt-0.5 text-xs ${alert ? 'font-medium text-red-600' : 'text-neutral-500'}`}>
                        {hint}
                    </p>
                )}
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