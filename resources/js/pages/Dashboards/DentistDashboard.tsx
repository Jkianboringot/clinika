import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

interface Stats {
    todayAppointments: number;
    completedToday: number;
    cancelledToday: number;
    totalPatientsTreated: number;
    pendingFollowUps: number;
}

type Status = 'scheduled' | 'completed' | 'cancelled' | 'no_show';

interface ScheduleItem {
    id: number;
    time: string;
    patient: string;
    reason: string | null;
    status: Status;
}

interface NextPatient {
    id: number;
    time: string;
    patient: string;
    reason: string | null;
}

interface WeekDay {
    label: string;
    count: number;
    isToday: boolean;
}

interface FollowUp {
    id: number;
    patient: string;
    procedure: string | null;
    date: string;
    daysAway: number;
}

interface RecentTreatment {
    id: number;
    patient: string;
    procedure: string | null;
    date: string | null;
}

interface DentistDashboardProps {
    dentist: { name: string };
    today: string;
    stats: Stats;
    nextPatient: NextPatient | null;
    schedule: ScheduleItem[];
    weekLoad: WeekDay[];
    followUps: FollowUp[];
    recentTreatments: RecentTreatment[];
}

export default function DentistDashboard({
    dentist,
    today,
    stats,
    nextPatient,
    schedule,
    weekLoad,
    followUps,
    recentTreatments,
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

                {/* Next patient */}
                <NextPatientBanner next={nextPatient} remaining={stats.todayAppointments - stats.completedToday - stats.cancelledToday} />

                {/* Stat cards */}
                <div className="grid auto-rows-min gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Today's Appointments"
                        value={stats.todayAppointments}
                        icon="📅"
                        accent="border-l-blue-500"
                        iconBg="bg-blue-100 dark:bg-blue-950"
                    />
                    <StatCard
                        label="Completed Today"
                        value={`${stats.completedToday} / ${stats.todayAppointments}`}
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

                {/* Main grid */}
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Today's schedule */}
                    <Panel title="Today's Schedule" subtitle={`${schedule.length} appointment${schedule.length === 1 ? '' : 's'}`} className="lg:col-span-2">
                        {schedule.length === 0 ? (
                            <Empty text="No appointments scheduled for today." />
                        ) : (
                            <ul className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                {schedule.map((a) => (
                                    <li key={a.id} className="flex items-center gap-4 py-3">
                                        <span className="w-20 shrink-0 text-sm font-semibold tabular-nums">{a.time}</span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">{a.patient}</p>
                                            <p className="truncate text-xs text-neutral-500">{a.reason ?? 'No reason given'}</p>
                                        </div>
                                        <StatusBadge status={a.status} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <div className="flex flex-col gap-4">
                        {/* Week load */}
                        <Panel title="This Week" subtitle="Appointments per day">
                            <WeekChart days={weekLoad} />
                        </Panel>

                        {/* Follow-ups */}
                        <Panel title="Upcoming Follow-ups">
                            {followUps.length === 0 ? (
                                <Empty text="No follow-ups scheduled." />
                            ) : (
                                <ul className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                    {followUps.map((f) => (
                                        <li key={f.id} className="flex items-center justify-between gap-3 py-2.5">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">{f.patient}</p>
                                                <p className="truncate text-xs text-neutral-500">{f.procedure ?? 'Follow-up visit'}</p>
                                            </div>
                                            <div className="shrink-0 text-right">
                                                <p className="text-sm font-semibold">{f.date}</p>
                                                <p className="text-xs text-neutral-500">
                                                    {f.daysAway === 0 ? 'Today' : f.daysAway === 1 ? 'Tomorrow' : `in ${f.daysAway} days`}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>
                    </div>
                </div>

                {/* Recent treatments */}
                <Panel title="Recent Treatments">
                    {recentTreatments.length === 0 ? (
                        <Empty text="No treatments recorded yet." />
                    ) : (
                        <ul className="divide-y divide-neutral-100 dark:divide-neutral-800">
                            {recentTreatments.map((t) => (
                                <li key={t.id} className="flex items-center justify-between gap-3 py-2.5">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">{t.patient}</p>
                                        <p className="truncate text-xs text-neutral-500">{t.procedure ?? 'Treatment'}</p>
                                    </div>
                                    <span className="shrink-0 text-xs text-neutral-500">{t.date}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>
        </>
    );
}

function NextPatientBanner({ next, remaining }: { next: NextPatient | null; remaining: number }) {
    if (!next) return null;

    return (
        <div className="flex items-center justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/40">
            <div className="min-w-0">
                <p className="text-xs font-medium text-blue-700 dark:text-blue-300">Next patient</p>
                <p className="truncate text-lg font-bold">{next.patient}</p>
                <p className="truncate text-sm text-neutral-600 dark:text-neutral-400">{next.reason ?? 'No reason given'}</p>
            </div>
            <div className="shrink-0 text-right">
                <p className="text-2xl font-bold tabular-nums">{next.time}</p>
                <p className="text-xs text-neutral-500">{remaining} left today</p>
            </div>
        </div>
    );
}

function Panel({
    title,
    subtitle,
    className = '',
    children,
}: {
    title: string;
    subtitle?: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <section
            className={`rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900 ${className}`}
        >
            <div className="mb-2 flex items-baseline justify-between">
                <h2 className="text-base font-semibold">{title}</h2>
                {subtitle && <span className="text-xs text-neutral-500">{subtitle}</span>}
            </div>
            {children}
        </section>
    );
}

function Empty({ text }: { text: string }) {
    return <p className="py-6 text-center text-sm text-neutral-500">{text}</p>;
}

const STATUS_STYLES: Record<Status, { label: string; className: string }> = {
    scheduled: { label: 'Scheduled', className: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' },
    completed: { label: 'Completed', className: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' },
    cancelled: { label: 'Cancelled', className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
    no_show: { label: 'No-show', className: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300' },
};

function StatusBadge({ status }: { status: Status }) {
    const s = STATUS_STYLES[status] ?? STATUS_STYLES.scheduled;
    return <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium ${s.className}`}>{s.label}</span>;
}

function WeekChart({ days }: { days: WeekDay[] }) {
    const max = Math.max(1, ...days.map((d) => d.count));
    return (
        <div className="flex h-32 items-end justify-between gap-2">
            {days.map((d) => (
                <div key={d.label} className="flex flex-1 flex-col items-center gap-1">
                    <span className="text-xs font-medium tabular-nums">{d.count}</span>
                    <div className="flex h-20 w-full items-end">
                        <div
                            className={`w-full rounded-t ${d.isToday ? 'bg-blue-500' : 'bg-neutral-200 dark:bg-neutral-700'}`}
                            style={{ height: `${Math.max(4, (d.count / max) * 100)}%` }}
                        />
                    </div>
                    <span className={`text-xs ${d.isToday ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-neutral-500'}`}>
                        {d.label}
                    </span>
                </div>
            ))}
        </div>
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