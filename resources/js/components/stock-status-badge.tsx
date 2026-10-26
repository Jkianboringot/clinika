import { AlertTriangle, Ban, CheckCircle2, XCircle } from 'lucide-react';

export type StockStatus = 'in_stock' | 'low' | 'critical' | 'out_stock';

const config: Record<StockStatus, { className: string; icon: typeof AlertTriangle }> = {
    in_stock: { className: 'bg-green-100 text-green-700', icon: CheckCircle2 },
    low: { className: 'bg-yellow-100 text-yellow-800', icon: AlertTriangle },
    critical: { className: 'bg-red-100 text-danger', icon: XCircle },
    out_stock: { className: 'bg-gray-200 text-gray-700', icon: Ban },
};

export default function StockStatusBadge({
    status,
    label,
}: {
    status: StockStatus;
    label: string;
}) {
    const { className, icon: Icon } = config[status];

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ${className}`}
        >
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}