import { useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { toast, Toaster } from 'sonner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Plus,
    XCircle,
} from 'lucide-react';

interface AppointmentRow {
    id: number;
    appointment_date: string;
    appointment_time: string;
    status: string | null;
    is_approve: string | null;
    procedure: { id: number; name: string } | null;
    dentist?: { id: number; name: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    appointments: {
        data: AppointmentRow[];
        links: PaginationLink[];
    };
}

interface FlashProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

function formatDate(value: string) {
    const [y, m, d] = value.split('-').map(Number);
    if (!y || !m || !d) return value;
    const month = new Date(y, m - 1, d).toLocaleString('en-US', { month: 'short' });
    return `${month} ${d}, ${y}`;
}

function formatTime(time: string) {
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 === 0 ? 12 : h % 12;
    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
}

function StatusBadge({ appt }: { appt: AppointmentRow }) {
    const status = (appt.status ?? '').toLowerCase();
    const approve = (appt.is_approve ?? '').toLowerCase();

    if (approve === 'pending') {
        return (
            <span className="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700 ring-1 ring-inset ring-yellow-200">
                Awaiting confirmation
            </span>
        );
    }

    if (approve === 'pending_reschedule' || approve === 'pendingreschedule') {
        return (
            <span className="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-200">
                Reschedule requested
            </span>
        );
    }

    const styles =
        status === 'completed'
            ? 'bg-green-50 text-green-700 ring-green-200'
            : status === 'cancelled'
                ? 'bg-red-50 text-red-700 ring-red-200'
                : 'bg-blue-50 text-blue-700 ring-blue-200';

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${styles}`}>
            {appt.status ?? 'Unknown'}
        </span>
    );
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index({ appointments }: Props) {
    const { flash } = usePage<FlashProps & Record<string, unknown>>().props as unknown as FlashProps;

    const lastFlashRef = useRef<string | null>(null);

    useEffect(() => {
        const current = flash?.error ?? flash?.success ?? null;
        if (!current || current === lastFlashRef.current) return;

        lastFlashRef.current = current;

        if (flash?.error) {
            toast.error(flash.error);
        } else if (flash?.success) {
            toast.success(flash.success);
        }
    }, [flash?.success, flash?.error]);

    const canChange = (appt: AppointmentRow) => {
        const status = (appt.status ?? '').toLowerCase();
        return status !== 'cancelled' && status !== 'completed';
    };

    const handleCancel = (appt: AppointmentRow) => {
        const label = `${formatDate(appt.appointment_date)} at ${formatTime(appt.appointment_time)}`;

        if (confirm(`Cancel your appointment on ${label}? This can't be undone.`)) {
            router.delete(`/patient/appointments/${appt.id}`);
        }
    };

    return (
        <>
            <Head title="My Appointments" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            My Appointments
                        </h1>
                        <p className="mt-1 text-sm text-subtle">Your upcoming and past visits</p>
                    </div>

                    <Link href="/patient/appointments/create">
                        <Button className="bg-blue-600 font-bold text-white hover:bg-blue-700">
                            <Plus className="h-4 w-4" />
                            Book Appointment
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="border-b border-gray-200 px-5 py-4">
                        <h2 className="text-sm font-bold text-ink">All Appointments</h2>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-gray-200 hover:bg-transparent">
                                <TableHead className="font-bold tracking-wide text-blue-700">Date</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Time</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Reason</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Dentist</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Status</TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-blue-700">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {appointments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        You have no appointments yet.
                                    </TableCell>
                                </TableRow>
                            )}

                            {appointments.data.map((appt) => (
                                <TableRow
                                    key={appt.id}
                                    className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                >
                                    <TableCell className="font-medium text-gray-900">
                                        {formatDate(appt.appointment_date)}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {formatTime(appt.appointment_time)}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {appt.procedure?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {appt.dentist?.name ?? 'Any available'}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge appt={appt} />
                                    </TableCell>
                                    <TableCell>
                                        {canChange(appt) && (
                                            <div className="flex items-center justify-end gap-4">
                                                <Link
                                                    href={`/patient/appointments/${appt.id}/questionnaire`}
                                                    className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-blue-700"
                                                >
                                                    <ClipboardList className="h-4 w-4" />
                                                    Questionnaire
                                                </Link>
                                                <Link
                                                    href={`/patient/appointments/${appt.id}/edit`}
                                                    className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-blue-700"
                                                >
                                                    <CalendarClock className="h-4 w-4" />
                                                    Reschedule
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleCancel(appt)}
                                                    className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-red-600"
                                                >
                                                    <XCircle className="h-4 w-4" />
                                                    Cancel
                                                </button>
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {appointments.links.length > 3 && (
                        <div className="flex gap-1 border-t border-gray-200 px-5 py-3">
                            {appointments.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${
                                        link.active ? 'bg-blue-600 text-white' : 'text-blue-700 hover:bg-gray-50'
                                    } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                >
                                    {paginationLabel(link.label)}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'My Appointments', href: '/patient/appointments' },
    ],
};