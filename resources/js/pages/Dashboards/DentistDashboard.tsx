import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface Stats {
    todayAppointments: number;
    patientsTreatedToday: number;
    totalPatientsTreated: number;
    pendingFollowUps: number;
}

interface ScheduleRow {
    id: number;
    time: string;
    patient: string;
    service: string;
    status: string;
}

interface CurrentPatient {
    name: string;
    age: number;
    gender: string;
    service: string;
    time: string;
    status: string;
}

interface DentistDashboardProps {
    dentist: { name: string };
    today: string;
    stats: Stats;
    schedule: ScheduleRow[];
    currentPatient: CurrentPatient | null;
}

const STATUS_STYLES: Record<string, string> = {
    completed: 'bg-green-600 text-white',
    in_progress: 'bg-blue-600 text-white',
    scheduled: 'bg-neutral-600 text-white',
    pending: 'bg-neutral-600 text-white',
    cancelled: 'bg-red-600 text-white',
};

const statusLabel = (s: string) =>
    s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const statusClass = (s: string) =>
    STATUS_STYLES[s.toLowerCase().replace(/\s+/g, '_')] ?? 'bg-neutral-600 text-white';

export default function DentistDashboard({
    dentist,
    today,
    stats,
    schedule,
    currentPatient,
}: DentistDashboardProps) {
    return (
        <>
            <Head title="Dentist Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold">Welcome back, Dr. {dentist.name}!</h1>
                    <p className="text-sm text-neutral-500">Here's your schedule for today, {today}</p>
                </div>

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
                        label="Patients Treated"
                        value={stats.patientsTreatedToday}
                        icon="✅"
                        accent="border-l-green-500"
                        iconBg="bg-green-100 dark:bg-green-950"
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

                {/* Schedule + current patient */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border lg:col-span-2">
                        <h3 className="font-semibold">Today's Schedule</h3>
                        <p className="mb-3 text-sm text-neutral-500">{today}</p>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b border-sidebar-border/70 font-semibold">
                                        <th className="py-2 pr-4">Time</th>
                                        <th className="py-2 pr-4">Patient</th>
                                        <th className="py-2 pr-4">Service</th>
                                        <th className="py-2 pr-4">Status</th>
                                        <th className="py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/50">
                                    {schedule.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="py-6 text-center text-neutral-500">
                                                No appointments scheduled for today.
                                            </td>
                                        </tr>
                                    )}
                                    {schedule.map((row) => (
                                        <tr key={row.id}>
                                            <td className="py-3 pr-4 font-semibold">{row.time}</td>
                                            <td className="py-3 pr-4">{row.patient}</td>
                                            <td className="py-3 pr-4">{row.service}</td>
                                            <td className="py-3 pr-4">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusClass(row.status)}`}
                                                >
                                                    {statusLabel(row.status)}
                                                </span>
                                            </td>
                                            <td className="py-3">
                                                <button
                                                    type="button"
                                                    className="rounded-md border border-sidebar-border/70 px-3 py-1 text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                                >
                                                    {row.status === 'in_progress' ? 'Continue' : 'View'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="rounded-xl border border-l-4 border-l-blue-500 border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="mb-4 font-semibold">Current Patient</h3>
                        {currentPatient ? (
                            <>
                                <h2 className="text-2xl font-bold">{currentPatient.name}</h2>
                                <p className="mb-4 text-neutral-500">
                                    Age {currentPatient.age} • {currentPatient.gender}
                                </p>
                                <ul className="space-y-2 text-sm">
                                    <DetailRow label="Service:" value={currentPatient.service} />
                                    <DetailRow label="Time:" value={currentPatient.time} />
                                    <li className="flex items-center justify-between">
                                        <span className="text-neutral-500">Status:</span>
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusClass(currentPatient.status)}`}
                                        >
                                            {statusLabel(currentPatient.status)}
                                        </span>
                                    </li>
                                </ul>
                            </>
                        ) : (
                            <p className="text-sm text-neutral-500">No patient in progress.</p>
                        )}
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

function DetailRow({ label, value }: { label: string; value: string | number }) {
    return (
        <li className="flex items-center justify-between">
            <span className="text-neutral-500">{label}</span>
            <strong>{value}</strong>
        </li>
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