import { Head, Link } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface Stats {
    upcomingAppointments: number;
    followUps: number;
}

interface UpcomingAppointment {
    id: number;
    procedure: string;
    dentist: string;
    date: string;
    time: string;
    status: string;
}

interface PatientDashboardProps {
    stats: Stats;
    upcomingAppointment: UpcomingAppointment | null;
}

const STATUS_STYLES: Record<string, string> = {
    confirmed: 'bg-blue-600 text-white',
    scheduled: 'bg-blue-600 text-white',
    pending: 'bg-neutral-600 text-white',
    in_progress: 'bg-blue-600 text-white',
};

const statusLabel = (s: string) =>
    s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const statusClass = (s: string) =>
    STATUS_STYLES[s.toLowerCase().replace(/\s+/g, '_')] ?? 'bg-neutral-600 text-white';

export default function PatientDashboard({ stats, upcomingAppointment }: PatientDashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                {/* Stat cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-2">
                    <StatCard
                        label="Upcoming Appointments"
                        value={stats.upcomingAppointments}
                        icon="📅"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <StatCard
                        label="Follow-up Schedule"
                        value={stats.followUps}
                        icon="⏰"
                        accent="border-l-orange-500"
                        iconBg="bg-orange-100 dark:bg-orange-950"
                    />
                </div>

                {/* Upcoming appointment */}
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h3 className="mb-4 font-semibold">Upcoming Appointment</h3>

                    {upcomingAppointment ? (
                        <div className="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-teal-50 p-5 dark:border-blue-900 dark:from-blue-950/40 dark:to-teal-950/30">
                            <div className="flex items-start justify-between">
                                <div>
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusClass(upcomingAppointment.status)}`}
                                    >
                                        {statusLabel(upcomingAppointment.status)}
                                    </span>
                                    <h2 className="mt-3 text-xl font-bold">{upcomingAppointment.procedure}</h2>
                                    <p className="text-neutral-500">{upcomingAppointment.dentist}</p>
                                </div>
                                <span className="text-4xl">📅</span>
                            </div>

                            <div className="mt-5 grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs text-neutral-500">Date</p>
                                    <p className="font-semibold">{upcomingAppointment.date}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-neutral-500">Time</p>
                                    <p className="font-semibold">{upcomingAppointment.time}</p>
                                </div>
                            </div>

                            <div className="mt-5 grid grid-cols-2 gap-3">
                                <Link
                                    href={`/appointments/${upcomingAppointment.id}`}
                                    className="rounded-md bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    View Details
                                </Link>
                                <Link
                                    href={`/appointments/${upcomingAppointment.id}/edit`}
                                    className="rounded-md border border-sidebar-border/70 bg-white px-4 py-2 text-center text-sm font-semibold hover:bg-neutral-100 dark:bg-neutral-900 dark:hover:bg-neutral-800"
                                >
                                    Reschedule
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-neutral-500">You have no upcoming appointments.</p>
                    )}
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

PatientDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};