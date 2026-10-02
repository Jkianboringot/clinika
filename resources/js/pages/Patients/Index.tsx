import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast, Toaster } from 'sonner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Eye, Pencil, Plus, Search, UserCheck, UserX, X } from 'lucide-react';
import { activate, create, deactivate, edit, index, show } from '@/routes/patients';
import { useCan } from '@/hooks/use-can';

interface PatientRow {
    id: number;
    full_name: string;
    date_of_birth: string | null;
    contact_number: string | null;
    email: string;
    is_active: string;
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
    patients: Paginated<PatientRow>;
    filters: { search?: string };
}

interface FlashProps {
    flash?: {
        message?: string;
        error?: string;
    };
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

function calculateAge(dob: string | null): number | null {
    if (!dob) return null;

    const birthDate = new Date(dob);
    if (isNaN(birthDate.getTime())) return null;

    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age;
}

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' });
    const day = date.getDate();
    const year = date.getFullYear();

    return `${month} ${day}, ${year}`;
}

export default function Index({ patients, filters }: Props) {
    const { flash } = usePage<FlashProps & Record<string, unknown>>().props as unknown as FlashProps;
    const can = useCan();

    // Guards against React 18 Strict Mode (createInertiaApp's `strictMode: true`)
    // double-invoking this effect on mount in development, which would
    // otherwise show the same toast twice. Doesn't affect production.
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

    const [search, setSearch] = useState(filters?.search ?? '');

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

    // Patients are soft-deactivated, never deleted.
    function handleDeactivate(patient: PatientRow) {
        if (
            confirm(
                `Deactivate ${patient.full_name}? They will no longer be able to log in.`,
            )
        ) {
            router.put(deactivate(patient.id).url, {}, { preserveScroll: true });
        }
    }

    // Reactivation only applies to currently deactivated patients. The backend
    // enforces this too, so a stale page can't activate an active patient.
    function handleActivate(patient: PatientRow) {
        if (patient.is_active === 'active') return;

        if (
            confirm(
                `Activate ${patient.full_name}? They will be able to log in again.`,
            )
        ) {
            router.put(activate(patient.id).url, {}, { preserveScroll: true });
        }
    }

    return (
        <>
            <Head title="Patient Records" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Patient Records
                        </h1>
                    </div>
                    {can('patients.create') && (
                        <Link href={create().url}>
                            <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                                <Plus className="h-4 w-4" />
                                Register New Patient
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 className="text-sm font-bold text-ink">All Patients</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search patients..."
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
                                    Name
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Date of Birth
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Age
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Contact
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Email
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Status
                                </TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-blue-700">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {patients.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-10 text-center text-sm text-subtle">
                                        No patients found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {patients.data.map((patient) => {
                                const isActive = patient.is_active === 'active';

                                return (
                                    <TableRow
                                        key={patient.id}
                                        className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                    >
                                        <TableCell className="font-medium text-blue-700">
                                            {patient.full_name}
                                        </TableCell>
                                        <TableCell className="text-gray-700">
                                            {formatDate(patient.date_of_birth)}
                                        </TableCell>
                                        <TableCell className="text-gray-700">
                                            {calculateAge(patient.date_of_birth) ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-gray-700">{patient.contact_number ?? '—'}</TableCell>
                                        <TableCell className="text-gray-700">{patient.email}</TableCell>
                                        <TableCell>
                                            <span
                                                className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${isActive
                                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                        : 'bg-red-50 text-red-700 ring-red-200'
                                                    }`}
                                            >
                                                <span
                                                    className={`h-1.5 w-1.5 rounded-full ${isActive ? 'bg-emerald-500' : 'bg-red-500'
                                                        }`}
                                                />
                                                {patient.is_active}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link
                                                    href={show(patient.id).url}
                                                    className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                >
                                                    <Eye className="h-3.5 w-3.5" />
                                                    View
                                                </Link>
                                                {can('patients.update') && (
                                                    <Link
                                                        href={edit(patient.id).url}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                        Edit
                                                    </Link>
                                                )}
                                                {can('patients.delete') && isActive && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeactivate(patient)}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                                                    >
                                                        <UserX className="h-3.5 w-3.5" />
                                                        Deactivate
                                                    </button>
                                                )}
                                                {can('patients.delete') && !isActive && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleActivate(patient)}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                                                    >
                                                        <UserCheck className="h-3.5 w-3.5" />
                                                        Activate
                                                    </button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    {patients.links.length > 3 && (
                        <div className="flex gap-1 border-t border-gray-200 px-5 py-3">
                            {patients.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${link.active
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
            title: 'Patients',
            href: '/patients',
        },
    ],
};