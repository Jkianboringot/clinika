import { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
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
import { Box, ChevronLeft, ChevronRight, Pencil, Search, Trash2 } from 'lucide-react';
import { deleteMethod, index as branchesIndex, products, edit } from '@/routes/branches';

interface Branch {
    id: number;
    location: string;
    name: string;
    branch_type: string;
    products_count: number;
}

interface PaginatedBranches {
    data: Branch[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    branches: PaginatedBranches;
    filters: { search?: string };
}

function BranchTypeBadge({ type }: { type: string }) {
    return (
        <span className="inline-flex items-center rounded-full border border-brand-orange/40 bg-[#fbead9] px-3 py-0.5 text-xs font-medium capitalize text-[#7a3b12]">
            {type}
        </span>
    );
}

// Laravel's paginator labels are always one of these three shapes —
// render icons for prev/next instead of trusting raw HTML entities.
function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    const { branches, filters } = usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps;
    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');

    // Branches are paginated server-side, so search has to round-trip to
    // the server — client-side filtering would only ever touch whichever
    // rows are on the current page.
    function applySearch(value: string) {
        setSearch(value);
        router.get(
            branchesIndex().url,
            { search: value },
            { preserveState: true, replace: true },
        );
    }

    const handleDelete = (id: number, location: string) => {
        if (confirm(`Delete "${location}"? This can't be undone.`)) {
            destroyForm(deleteMethod(id).url);
        }
    };

    return (
        <>
            <Head title="Branches" />

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Branches
                    </h1>
                    <Link href={'/branches/create'}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New branch
                        </Button>
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#f0ddc8] bg-[#fdf8f2]">
                    <div className="flex items-center justify-end gap-3 border-b border-[#f0ddc8] px-5 py-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => applySearch(e.target.value)}
                                placeholder="Search"
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#f0ddc8] bg-[#fbead9] hover:bg-[#fbead9]">
                                 <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    Name
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    LOCATION
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    BRANCH TYPE
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    PRODUCTS
                                </TableHead>
                                <TableHead className="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {branches.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-10 text-center text-sm text-subtle">
                                        No branches found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {branches.data.map((branch) => (
                                <TableRow
                                    key={branch.id}
                                    className="border-b border-[#f0ddc8] last:border-0 hover:bg-[#fbf3e8]"
                                >
                                     <TableCell className="font-medium text-[#7a3b12]">
                                        {branch.name}
                                    </TableCell>
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {branch.location}
                                    </TableCell>
                                    <TableCell>
                                        <BranchTypeBadge type={branch.branch_type} />
                                    </TableCell>
                                    <TableCell>{branch.products_count}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-4">
                                            <Link
                                                href={products(branch.id).url}
                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Box className="h-4 w-4" />
                                                View Products
                                            </Link>
                                            <Link
                                                href={edit(branch.id).url}

                                                className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                            >
                                                <Pencil className="h-4 w-4" />
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                disabled={processing}
                                                onClick={() => handleDelete(branch.id, branch.location)}
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

                    {branches.links.length > 3 && (
                        <div className="flex gap-1 border-t border-[#f0ddc8] px-5 py-3">
                            {branches.links.map((link, i) => (
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
            title: 'Branches',
            href: '/branches',
        },
    ],
};