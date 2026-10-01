import { Head, Link } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface Stats {
    todayAppointments: number;
    totalPatients: number;
    lowStockItems: number;
    inventoryItems: number;
}

interface LowStock {
    id: number;
    name: string;
    current: number;
    minimum: number;
    percent: number;
    critical: boolean;
}

interface StaffDashboardProps {
    stats: Stats;
    lowStock: LowStock[];
}

export default function StaffDashboard({ stats, lowStock }: StaffDashboardProps) {
    return (
        <>
            <Head title="Staff Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Critical banner */}
                {stats.lowStockItems > 0 && (
                    <div className="flex items-start gap-4 rounded-xl border border-l-4 border-red-200 border-l-red-600 bg-red-50 p-5 dark:border-red-900 dark:border-l-red-600 dark:bg-red-950/30">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-lg dark:bg-red-950">
                            ⚠️
                        </div>
                        <div>
                            <h2 className="text-lg font-semibold">
                                Critical: {stats.lowStockItems} {stats.lowStockItems === 1 ? 'Item' : 'Items'} Low in Stock!
                            </h2>
                            <p className="mb-3 text-sm text-neutral-600 dark:text-neutral-400">
                                Inventory items are at or below their minimum stock levels. Reorder soon to avoid supply shortages.
                            </p>
                           
                        </div>
                    </div>
                )}

                {/* Stat cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Today's Appointments"
                        value={stats.todayAppointments}
                        icon="📅"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <StatCard
                        label="Total Patients"
                        value={stats.totalPatients}
                        icon="👥"
                        accent="border-l-teal-500"
                        iconBg="bg-teal-100 dark:bg-teal-950"
                    />
                    <StatCard
                        label="Low Stock Items"
                        value={stats.lowStockItems}
                        icon="📉"
                        accent="border-l-red-500"
                        iconBg="bg-red-100 dark:bg-red-950"
                    />
                    <StatCard
                        label="Inventory Items"
                        value={stats.inventoryItems}
                        icon="📦"
                        accent="border-l-green-500"
                        iconBg="bg-green-100 dark:bg-green-950"
                    />
                </div>

                {/* Low stock list + quick actions */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="mb-4 font-semibold">⚠️ Low Stock Alerts</h3>

                        {lowStock.length === 0 ? (
                            <p className="text-sm text-neutral-500">All items are above their minimum stock.</p>
                        ) : (
                            <div className="space-y-3">
                                {lowStock.map((item) => (
                                    <div
                                        key={item.id}
                                        className={`rounded-lg border p-3 ${
                                            item.critical
                                                ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30'
                                                : 'border-orange-200 bg-orange-50 dark:border-orange-900 dark:bg-orange-950/30'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">{item.name}</span>
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-semibold text-white ${
                                                    item.critical ? 'bg-red-600' : 'bg-orange-500'
                                                }`}
                                            >
                                                {item.critical ? 'Critical' : 'Low'}
                                            </span>
                                        </div>
                                        <p className="mb-2 text-sm text-neutral-500">
                                            Current: {item.current} | Minimum: {item.minimum}
                                        </p>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                className={`h-full rounded-full ${item.critical ? 'bg-red-600' : 'bg-orange-500'}`}
                                                style={{ width: `${item.percent}%` }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="mb-4 font-semibold">Quick Actions</h3>
                        <div className="space-y-2">
                            <Link
                                href="/appointments"
                                className="flex items-center gap-3 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                📅 Manage Appointments
                            </Link>
                            <Link
                                href="/patients/create"
                                className="flex items-center gap-3 rounded-md border border-sidebar-border/70 px-4 py-2 text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            >
                                👤 Register New Patient
                            </Link>
                            <Link
                                href="/inventory"
                                className="flex items-center gap-3 rounded-md border border-sidebar-border/70 px-4 py-2 text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800"
                            >
                                📦 Update Inventory
                            </Link>
                        </div>
                    </div>
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

StaffDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};