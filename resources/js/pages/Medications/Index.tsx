import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast, Toaster } from 'sonner';
import { ChevronLeft, ChevronRight, Pencil, Search, Trash2 } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, destroy, edit, index as medicationsIndex } from '@/routes/medications';

interface Medication {
    id: number;
    name: string;
    generic_name: string | null;
    dosage_form: string | null;
    strength: string | null;
    created_at: string;
}

interface PaginatedMedications {
    data: Medication[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface MedicationsPageProps extends Record<string, unknown> {
    flash: {
        message?: string;
        error?: string;
    };
    medications: PaginatedMedications;
    filters: {
        search?: string;
    };
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { flash, medications, filters } = usePage<MedicationsPageProps>().props;

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

    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');

    const medicationRows = medications?.data ?? [];

    useEffect(() => {
        if (search === (filters?.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                medicationsIndex().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, filters?.search]);

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(destroy(id).url);
        }
    };

    return (
        <>
            <Head title="Medications" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Medications
                        </h1>
                        
                    </div>
                    <Link href={create().url}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New medication
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#c9c0b7]  bg-white">
                    <div className="flex items-center justify-between border-b border-[#c9c0b7] px-5 py-3">
                        <h2 className="font-bold text-ink">All Medications</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search medications..."
                                maxLength={100}
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#c9c0b7]">
                                <TableHead className="font-bold text-brand-orange-hover">Name</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Generic name</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Form</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Strength</TableHead>
                                <TableHead className="text-right font-bold text-brand-orange-hover">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {medicationRows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No medications found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {medicationRows.map((medication) => (
                                <TableRow
                                    key={medication.id}
                                    className="border-b border-[#c9c0b7] last:border-0"
                                >
                                    <TableCell>{medication.name}</TableCell>
                                    <TableCell>{medication.generic_name ?? '—'}</TableCell>
                                    <TableCell>{medication.dosage_form ?? '—'}</TableCell>
                                    <TableCell>{medication.strength ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(medication.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(medication.id, medication.name)}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger disabled:opacity-50"
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

                    {medications?.links?.length > 3 && (
                        <div className="flex gap-1 border-t border-[#c9c0b7] px-5 py-3">
                            {medications.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${link.active
                                        ? 'bg-brand-orange text-white'
                                        : 'text-brand-orange-hover hover:bg-[#fbead9]'
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
        { title: 'Medications', href: '/medications' },
    ],
};