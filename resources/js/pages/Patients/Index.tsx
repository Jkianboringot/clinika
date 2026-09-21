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
import { ChevronLeft, ChevronRight, Eye, Plus, Search, X } from 'lucide-react';
import { create, index } from '@/routes/patients';

interface PatientRow {
    id: number;
    full_name: string;
    age: number | null;
    contact_number: string | null;
    email: string;
    last_visit: string | null;
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

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index({ patients, filters }: Props) {
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

    return (
        <>
            <Head title="Patient Records" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Patient Records
                        </h1>
                        <p className="mt-1 text-sm text-subtle">
                            Manage patient information and records
                        </p>
                    </div>
                    <Link href={create().url}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            <Plus className="h-4 w-4" />
                            Register New Patient
                        </Button>
                    </Link>
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
                                    Patient ID
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Name
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
                                    Last Visit
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
                            {patients.data.map((patient) => (
                                <TableRow
                                    key={patient.id}
                                    className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                >
                                    <TableCell className="text-gray-700">
                                        {patient.id}
                                    </TableCell>
                                    <TableCell className="font-medium text-blue-700">
                                        {patient.full_name}
                                    </TableCell>
                                    <TableCell className="text-gray-700">{patient.age ?? '—'}</TableCell>
                                    <TableCell className="text-gray-700">{patient.contact_number ?? '—'}</TableCell>
                                    <TableCell className="text-gray-700">{patient.email}</TableCell>
                                    <TableCell className="text-gray-500">
                                        {patient.last_visit ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Link
                                            href={show(patient.id).url}
                                            className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye className="h-3.5 w-3.5" />
                                            View
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {patients.links.length > 3 && (
                        <div className="flex gap-1 border-t border-gray-200 px-5 py-3">
                            {patients.links.map((link, i) => (
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
            title: 'Patients',
            href: '/patients',
        },
    ],
};