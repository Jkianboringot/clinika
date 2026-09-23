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
import { ChevronLeft, ChevronRight, Pencil, Plus, Search, X } from 'lucide-react';
import { edit, index } from '@/routes/inventories';

interface InventoryStockItem {
    id: number;
    item_name: string;
    quantity: number;
}

interface InventoryRow {
    id: number;
    staff_name: string | null;
    items: InventoryStockItem[];
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
    inventories?: Paginated<InventoryRow>;
    filters?: { search?: string };
}

const EMPTY_PAGINATED: Paginated<InventoryRow> = { data: [], links: [] };

function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

function totalQuantity(items: InventoryStockItem[]) {
    return items.reduce((sum, item) => sum + (item.quantity ?? 0), 0);
}

export default function Index({ inventories, filters }: Props) {
    if (!inventories) {
        console.warn(
            '[Inventories/Index] "inventories" prop is missing or undefined. ' +
            'Check that the controller passes it via Inertia::render with a { data, links } shape.',
        );
    }

    const safeInventories = inventories ?? EMPTY_PAGINATED;
    const safeFilters = filters ?? {};

    const [search, setSearch] = useState(safeFilters.search ?? '');

    useEffect(() => {
        if (search === (safeFilters.search ?? '')) return;

        const timeout = setTimeout(() => {
            router.get(
                index().url,
                { search },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => clearTimeout(timeout);
    }, [search, safeFilters.search]);

    function clearSearch() {
        setSearch('');
        router.get(index().url, {}, { preserveState: true, replace: true });
    }

    return (
        <>
            <Head title="Inventory" />

            <div className="p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Inventory
                        </h1>
                        <p className="mt-1 text-sm text-subtle">
                            Stock additions recorded by staff
                        </p>
                    </div>
                    <Link
                        href="/inventories/create"
                        className="inline-flex items-center gap-1.5 rounded-md bg-brand-orange px-4 py-2 text-sm font-bold text-white hover:bg-brand-orange-hover"
                    >
                        <Plus className="h-4 w-4" />
                        Add Stock
                    </Link>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h2 className="text-sm font-bold text-ink">All Inventory Entries</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by staff or item..."
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
                                    Inventory ID
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Staff
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Items
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Total Quantity
                                </TableHead>
                                <TableHead className="font-bold tracking-wide text-blue-700">
                                    Date
                                </TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-blue-700">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {safeInventories.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No inventory entries found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {safeInventories.data.map((inventory) => (
                                <TableRow
                                    key={inventory.id}
                                    className="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                                >
                                    <TableCell className="text-gray-700">
                                        {inventory.id}
                                    </TableCell>
                                    <TableCell className="font-medium text-blue-700">
                                        {inventory.staff_name ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {inventory.items.length === 0 ? (
                                            '—'
                                        ) : (
                                            <ul className="space-y-0.5">
                                                {inventory.items.map((item) => (
                                                    <li key={item.id}>
                                                        {item.item_name}{' '}
                                                        <span className="text-gray-400">×{item.quantity}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-gray-700">
                                        {totalQuantity(inventory.items)}
                                    </TableCell>
                                    <TableCell className="text-gray-500">
                                        {inventory.created_at ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Link
                                                href={edit(inventory.id).url}
                                                className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                                Edit
                                            </Link>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {safeInventories.links.length > 3 && (
                        <div className="flex gap-1 border-t border-gray-200 px-5 py-3">
                            {safeInventories.links.map((link, i) => (
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
            title: 'Inventory',
            href: '/inventories',
        },
    ],
};