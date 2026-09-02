import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ArrowLeft } from 'lucide-react';
import { index } from '@/routes/branches';

interface BranchProduct {
    id: number;
    name: string;
    pivot: {
        quantity: number;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    branch: {
        id: number;
        location: string;
    };
    products: Paginated<BranchProduct>;
}

export default function BranchProducts({ branch, products }: Props) {
    return (
        <>
            <Head title={`Products at ${branch.location}`} />

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <Link
                            href={index().url}
                            className="mb-1 flex items-center gap-1 text-sm font-medium text-subtle hover:text-brand-orange"
                        >
                            <ArrowLeft className="h-3.5 w-3.5" />
                            Back to Branches
                        </Link>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Products at {branch.location}
                        </h1>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-[#f0ddc8] bg-[#fdf8f2]">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#f0ddc8] bg-[#fbead9] hover:bg-[#fbead9]">
                                <TableHead className="font-bold tracking-wide text-brand-orange-hover">
                                    PRODUCT
                                </TableHead>
                                <TableHead className="text-right font-bold tracking-wide text-brand-orange-hover">
                                    QUANTITY
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={2} className="py-10 text-center text-sm text-subtle">
                                        This branch has no products yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {products.data.map((product) => (
                                <TableRow
                                    key={product.id}
                                    className="border-b border-[#f0ddc8] last:border-0 hover:bg-[#fbf3e8]"
                                >
                                    <TableCell className="font-medium text-[#7a3b12]">
                                        {product.name}
                                    </TableCell>
                                    <TableCell className="text-right font-medium">
                                        {product.pivot.quantity}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {products.data.length > 0 && (
                        <div className="flex items-center justify-between px-5 py-3 text-sm text-brand-orange">
                            <span>
                                Showing {products.from} to {products.to} of {products.total} results
                            </span>
                            <div className="flex items-center gap-1">
                                {products.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        preserveScroll
                                        className={`rounded-md px-3 py-1.5 text-sm ${
                                            link.active
                                                ? 'bg-brand-orange font-bold text-white'
                                                : 'text-subtle hover:bg-[#fbead9]'
                                        } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

BranchProducts.layout = {
    breadcrumbs: [
        { title: 'Branches Produce', href: '/branches' },
    ],
};