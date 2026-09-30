import { Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { index as inventoryitemsIndex } from '@/routes/inventory_items';

export interface LowStockItem {
    id: number;
    item_name: string;
    stock: number;
    minimum_stock: number;
}

interface Props {
    items?: LowStockItem[];
    /** show a "View inventory" link (use on the dashboard) */
    showLink?: boolean;
    /** how many items to list before "…and N more" */
    limit?: number;
}

const fmt = (n: number) => (Number.isInteger(Number(n)) ? String(n) : Number(n).toFixed(2));

export default function LowStockAlert({ items = [], showLink = false, limit = 5 }: Props) {
    if (items.length === 0) return null;

    const shown = items.slice(0, limit);
    const rest = items.length - shown.length;

    return (
        <div
            role="alert"
            className="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
        >
            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0" />
            <div className="flex-1">
                <p className="font-semibold">
                    Low stock alert: {items.length} {items.length === 1 ? 'item is' : 'items are'} below the minimum
                    stock
                </p>
                <ul className="mt-1 space-y-0.5 text-sm">
                    {shown.map((item) => (
                        <li key={item.id}>
                            <strong>{item.item_name}</strong> — {fmt(item.stock)} left (minimum {fmt(item.minimum_stock)})
                        </li>
                    ))}
                </ul>
                {rest > 0 && <p className="mt-1 text-sm">…and {rest} more</p>}
            </div>
            {showLink && (
                <Link
                    href={inventoryitemsIndex().url}
                    className="shrink-0 text-sm font-semibold underline hover:no-underline"
                >
                    View inventory
                </Link>
            )}
        </div>
    );
}
