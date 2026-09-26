import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ChevronLeft, ChevronRight, Megaphone, Pencil, Search, Trash2 } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, destroy, edit, index as proceduresIndex } from '@/routes/procedures';

interface Procedure {
    id: number;
    name: string;
    description: string | null;
    duration: number | null;
    price: number | string;
    is_active: boolean;
    created_at: string;
}

interface PaginatedProcedures {
    data: Procedure[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface ProceduresPageProps {
    flash: {
        message?: string;
        error?: string;
    };
    procedures: PaginatedProcedures;
    filters: { search?: string };
}

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

function formatPrice(price: number | string) {
    const value = typeof price === 'string' ? parseFloat(price) : price;
    if (Number.isNaN(value)) return '—';
    return `$${value.toFixed(2)}`;
}

function formatDuration(duration: number | null) {
    if (duration === null || duration === undefined) return '—';
    return `${duration} min`;
}
export default function Index() {
const { flash, procedures, filters } = usePage< ProceduresPageProps & Record<string, unknown> >().props as unknown as ProceduresPageProps;

    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');
    const isFirstRender = useRef(true);

    const procedureRows = procedures?.data ?? [];

    // Debounced search — same 400ms pattern as Users/Index.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                proceduresIndex().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search]);

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Delete "${name}"? This can't be undone.`)) {
            destroyForm(destroy(id).url);
        }
    };

    return (
        <>
            <Head title="Procedures" />

            <div className="p-6">
                {flash?.message && (
                    <div className="mb-4">
                        <Alert>
                            <Megaphone />
                            <AlertTitle>Notification</AlertTitle>
                            <AlertDescription>{flash.message}</AlertDescription>
                        </Alert>
                    </div>
                )}

                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Procedures
                        </h1>
                       
                    </div>
                    <Link href={create().url}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New procedure
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#c9c0b7]">
                    <div className="flex items-center justify-between border-b border-[#c9c0b7] px-5 py-3">
                        <h2 className="font-bold text-ink">All Procedures</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search procedures..."
                                maxLength={100}
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#c9c0b7]">
                                <TableHead className="font-bold text-brand-orange-hover">Name</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Duration</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Price</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Status</TableHead>
                                <TableHead className="text-right font-bold text-brand-orange-hover">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {procedureRows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No procedures found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {procedureRows.map((procedure) => (
                                <TableRow
                                    key={procedure.id}
                                    className="border-b border-[#c9c0b7] last:border-0"
                                >
                                    <TableCell>{procedure.name}</TableCell>
                                    <TableCell>{formatDuration(procedure.duration)}</TableCell>
                                    <TableCell>{formatPrice(procedure.price)}</TableCell>
                                    <TableCell>
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${procedure.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-500'
                                                }`}
                                        >
                                            {procedure.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={edit(procedure.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(procedure.id, procedure.name)}
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

                    {procedures?.links?.length > 3 && (
                        <div className="flex gap-1 border-t border-[#c9c0b7] px-5 py-3">
                            {procedures.links.map((link, i) => (
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
        {
            title: 'Procedures',
            href: '/procedures',
        },
    ],
};