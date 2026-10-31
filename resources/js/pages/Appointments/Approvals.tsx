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
import { Check, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { useCan } from '@/hooks/use-can';

type ApproveState = 'pending' | 'pending_reschedule' | 'reject' | 'approve';

interface AppointmentRow {
    id: number;
    appointment_date: string;
    appointment_time: string;
    is_approve: ApproveState;
    patient: { id: number; full_name: string } | null;
    procedure: { id: number; name: string } | null;
    dentist: { id: number; name: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
}

interface Props {
    appointments: Paginated<AppointmentRow>;
    tab: 'pending' | 'rejected';
    counts: { pending: number; rejected: number };
}

interface FlashProps {
    flash?: {
        message?: string;
        error?: string;
    };
}

const BASE_URL = '/appointment-approvals';

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

function formatTime(time: string) {
    // "14:30" -> "2:30 PM"
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 === 0 ? 12 : h % 12;
    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
}

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' });
    return `${month} ${date.getDate()}, ${date.getFullYear()}`;
}

function ApprovalBadge({ state }: { state: ApproveState }) {
    const config: Record<ApproveState, { label: string; styles: string; dot: string }> = {
        pending: {
            label: 'Pending',
            styles: 'bg-yellow-50 text-yellow-700 ring-yellow-200',
            dot: 'bg-yellow-500',
        },
        pending_reschedule: {
            label: 'Reschedule pending',
            styles: 'bg-purple-50 text-purple-700 ring-purple-200',
            dot: 'bg-purple-500',
        },
        reject: {
            label: 'Rejected',
            styles: 'bg-red-50 text-red-700 ring-red-200',
            dot: 'bg-red-500',
        },
        approve: {
            label: 'Approved',
            styles: 'bg-green-50 text-green-700 ring-green-200',
            dot: 'bg-green-500',
        },
    };

    const c = config[state] ?? config.pending;

    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${c.styles}`}
        >
            <span className={`mr-1.5 h-1.5 w-1.5 rounded-full ${c.dot}`} />
            {c.label}
        </span>
    );
}

export default function Approvals({ appointments, tab, counts }: Props) {
    const { flash } = usePage<FlashProps & Record<string, unknown>>().props as unknown as FlashProps;
    const can = useCan();

    // Same strict-mode guard as the appointments list, so a toast doesn't show twice in dev.
    const lastFlashRef = useRef<string | null>(null);

    useEffect(() => {
        const current = flash?.error ?? flash?.message ?? null;
        if (!current || current === lastFlashRef.current) return;

        lastFlashRef.current = current;

        if (flash?.error) {
            toast.error(flash.error);
        } else if (flash?.message) {
            toast.success(flash.message);
        }
    }, [flash?.message, flash?.error]);

    const act = (id: number, action: 'approve' | 'reject') => {
        router.put(`${BASE_URL}/${id}/${action}`, {}, { preserveScroll: true });
    };

    const handleReject = (id: number, patientName: string) => {
        if (confirm(`Reject the appointment for "${patientName}"?`)) {
            act(id, 'reject');
        }
    };

    const tabClass = (active: boolean) =>
        `rounded-md px-3 py-1.5 text-sm font-semibold ${
            active ? 'bg-blue-600 text-white' : 'text-blue-700 hover:bg-gray-50'
        }`;

    return (
        <>
            <Head title="Appointment Approvals" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Appointment Approvals
                    </h1>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center gap-2 border-b border-gray-200 px-5 py-4">
                        <Link href={BASE_URL} className={tabClass(tab === 'pending')}>
                            Pending ({counts.pending})
                        </Link>
                        <Link href={`${BASE_URL}?tab=rejected`} className={tabClass(tab === 'rejected')}>
                            Rejected ({counts.rejected})
                        </Link>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-gray-200 hover:bg-transparent">
                                <TableHead className="font-bold tracking-wide text-blue-700">Patient</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Procedure</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Dentist</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Date of Appointment</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Time</TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">Approval</TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-blue-700">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {appointments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-10 text-center text-sm text-subtle">
                                        {tab === 'rejected'
                                            ? 'No rejected appointments.'
                                            : 'No appointments waiting for approval.'}
                                    </TableCell>
                                </TableRow>
                            )}
                            {appointments.data.map((appt) => (
                                <TableRow
                                    key={appt.id}
                                    className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                >
                                    <TableCell className="font-medium text-blue-700">
                                        {appt.patient?.full_name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-gray-700">{appt.procedure?.name ?? '—'}</TableCell>
                                    <TableCell className="text-gray-700">{appt.dentist?.name ?? '—'}</TableCell>
                                    <TableCell className="text-gray-700">{formatDate(appt.appointment_date)}</TableCell>
                                    <TableCell className="text-gray-700">{formatTime(appt.appointment_time)}</TableCell>
                                    <TableCell>
                                        <ApprovalBadge state={appt.is_approve} />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            {can('appointments.update') && (
                                                <button
                                                    type="button"
                                                    onClick={() => act(appt.id, 'approve')}
                                                    className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-green-700"
                                                >
                                                    <Check className="h-4 w-4" />
                                                    Approve
                                                </button>
                                            )}
                                            {can('appointments.update') && appt.is_approve !== 'reject' && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleReject(appt.id, appt.patient?.full_name ?? `#${appt.id}`)
                                                    }
                                                    className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-danger"
                                                >
                                                    <X className="h-4 w-4" />
                                                    Reject
                                                </button>
                                            )}
                                        </div>
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

Approvals.layout = {
    breadcrumbs: [
        {
            title: 'Appointment Approvals',
            href: '/appointment-approvals',
        },
    ],
};