import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage /*, useForm */ } from '@inertiajs/react';
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
import { ChevronLeft, ChevronRight, Pencil, Search, /* Trash2, */ X } from 'lucide-react';
import { edit, index /*, destroy */ } from '@/routes/treatments';
import { useCan } from '@/hooks/use-can';

interface TreatmentRow {
    id: number;
    patient_name: string | null;
    appointment_id: number;
    follow_up_schedule: string | null;
    duration_minutes: number | null;
    summary: string | null;
    created_at: string | null;
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
    treatments: Paginated<TreatmentRow>;
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

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' }); // "Sep"
    const day = date.getDate();                                     // 25
    const year = date.getFullYear();                                // 2026

    return `${month} ${day}, ${year}`; // e.g. "Sep 25, 2026" — matches the Date column's format
}

export default function Index({ treatments, filters }: Props) {
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
    // const { processing, delete: destroyForm } = useForm();

    // Tracks which rows' Summary cell is expanded to full text (by treatment id).
    const [expandedSummaries, setExpandedSummaries] = useState<Set<number>>(new Set());

    const toggleSummary = (id: number) => {
        setExpandedSummaries((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

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

    // DELETE disabled for now. To re-enable: uncomment this, the useForm line above,
    // the imports (useForm, Trash2, destroy) and the Delete button below.
    // const handleDelete = (id: number, patientName: string) => {
    //     if (confirm(`Delete the treatment for "${patientName}"? This can't be undone.`)) {
    //         destroyForm(destroy(id).url);
    //     }
    // };

    return (
        <>
            <Head title="Treatments" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Treatments
                        </h1>

                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 className="text-sm font-bold text-ink">All Treatments</h2>
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
                                    Appointment
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Duration
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Follow-up
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Summary
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Date
                                </TableHead>
                                {can('treatments.update') && (
                                    <TableHead className="text-right font-bold tracking-wide text-blue-700">
                                        Actions
                                    </TableHead>
                                )}

                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {treatments.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-10 text-center text-sm text-subtle">
                                        No treatments found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {treatments.data.map((treatment) => {
                                const isExpanded = expandedSummaries.has(treatment.id);
                                const summaryText = treatment.summary ?? '—';
                                // Only offer the toggle when there's actually enough text to hide.
                                const isLong = (treatment.summary?.length ?? 0) > 60;

                                return (
                                    <TableRow
                                        key={treatment.id}
                                        className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                    >

                                        <TableCell className="font-medium text-blue-700">
                                            {treatment.patient_name ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-gray-700">
                                            #{treatment.appointment_id}
                                        </TableCell>
                                        <TableCell className="text-gray-700">
                                            {treatment.duration_minutes ? `${treatment.duration_minutes} min` : '—'}
                                        </TableCell>
                                        <TableCell className="text-gray-700">
                                            {formatDate(treatment.follow_up_schedule)}
                                        </TableCell>
                                        <TableCell className="max-w-xs text-gray-700">
                                            <span className={isExpanded ? 'block whitespace-pre-wrap break-words' : 'block truncate'}>
                                                {summaryText}
                                            </span>
                                            {isLong && (
                                                <button
                                                    type="button"
                                                    onClick={() => toggleSummary(treatment.id)}
                                                    className="mt-0.5 text-xs font-medium text-blue-600 hover:underline"
                                                >
                                                    {isExpanded ? 'Show less' : 'Show more'}
                                                </button>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-gray-500">
                                            {treatment.created_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {can('treatments.update') && (
                                                    <Link
                                                        href={edit(treatment.id).url}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                        Edit
                                                    </Link>
                                                )}
                                                {/* DELETE disabled for now
                                                {can('treatments.delete') && (
                                                    <button
                                                        type="button"
                                                        disabled={processing}
                                                        onClick={() =>
                                                            handleDelete(
                                                                treatment.id,
                                                                treatment.patient_name ?? `#${treatment.id}`,
                                                            )
                                                        }
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-danger disabled:opacity-50"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        Delete
                                                    </button>
                                                )}
                                                */}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    {treatments.links.length > 3 && (
                        <div className="flex gap-1 border-t border-gray-200 px-5 py-3">
                            {treatments.links.map((link, i) => (
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
            title: 'Treatments',
            href: '/treatments',
        },
    ],
};