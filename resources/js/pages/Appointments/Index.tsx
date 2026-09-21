import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Pencil, Plus, Search, Trash2, X } from 'lucide-react';
import { create, edit, index, destroy } from '@/routes/appointments';
import { useForm } from '@inertiajs/react';

interface AppointmentRow {
    id: number;
    appointment_date: string;
    appointment_time: string;
    description: string | null;
    patient: { id: number; full_name: string } | null;
    staff: { id: number; name: string } | null;
    procedure: { id: number; name: string } | null;
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
    filters: { search?: string };
}

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

export default function Index({ appointments, filters }: Props) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const { processing, delete: destroyForm } = useForm();

    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    function clearSearch() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    }

    const handleDelete = (id: number, patientName: string) => {
        if (confirm(`Delete the appointment for "${patientName}"? This can't be undone.`)) {
            destroyForm(destroy(id).url);
        }
    };

    return (
        <>
            <Head title="Appointments" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Appointments
                        </h1>
                        <p className="mt-1 text-sm text-subtle">
                            Schedule and manage patient visits
                        </p>
                    </div>
                    <Link href={create().url}>
                        <Button className="bg-blue-600 font-bold text-white hover:bg-blue-700">
                            <Plus className="h-4 w-4" />
                            New Appointment
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 className="text-sm font-bold text-ink">All Appointments</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by patient..."
                                className="w-64 border-gray-200 bg-gray-50 pl-9 pr-8 text-sm"
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={clearSearch}
                                    aria-label="Clear search"
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-subtle hover:text-brand-orange"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-gray-200 hover:bg-transparent">
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Patient
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Procedure
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Staff
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Date
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Time
                                </TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-blue-700">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {appointments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No appointments found.
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
                                    <TableCell className="text-gray-700">
                                        {appt.procedure?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {appt.staff?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {appt.appointment_date}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {formatTime(appt.appointment_time)}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(appt.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-blue-700"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(appt.id, appt.patient?.full_name ?? `#${appt.id}`)}
                                                className="flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-danger disabled:opacity-50"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                                Delete
                                            </button>
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
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-blue-700 hover:bg-gray-50'
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
        {
            title: 'Appointments',
            href: '/appointments',
        },
    ],
};