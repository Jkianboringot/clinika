import { Head, router, usePage } from '@inertiajs/react';
import { Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';

// ============================================================
// ROLE & PERMISSION TOGGLE
// false = permissions DISABLED (development/testing)
// true  = permissions ENABLED
// ============================================================
const ENABLE_PERMISSIONS = false;

interface Column {
    key: string;
    label: string;
    align?: 'right';
}

interface Filters {
    from: string | null;
    to: string | null;
    status: string | null;
    search: string | null;
}

interface PageProps {
    types: { value: string; label: string }[];
    type: string;
    filters: Filters;
    columns: Column[];
    rows: Record<string, string | number>[];
    summary: { label: string; value: number }[];
    statuses: { value: string; label: string }[];
    generatedAt: string;
}

const fieldClass =
    'h-9 rounded-md border border-[#d1d5db] bg-white px-3 text-sm text-ink focus:border-brand-orange focus:outline-none';

// Highlight for the inventory "Status" column (values come from StockStatusEnum::label()).
const stockStatusClass: Record<string, string> = {
    'Low Stock': 'font-bold text-yellow-700',
    Critical: 'font-bold text-red-600',
};

const emptyFilters: Filters = {
    from: null,
    to: null,
    status: null,
    search: null,
};

function buildQuery(type: string, f: Filters) {
    const params = new URLSearchParams({ type });

    (Object.keys(f) as (keyof Filters)[]).forEach((k) => {
        if (f[k]) {
            params.set(k, String(f[k]));
        }
    });

    return params.toString();
}

export default function Index() {
    const can = useCan();

    const props = usePage<PageProps & Record<string, unknown>>()
        .props as unknown as PageProps;

    const { types, type, columns, rows, summary, statuses, generatedAt } = props;

    const [filters, setFilters] = useState<Filters>(props.filters);

    const apply = (nextType = type, next = filters) => {
        router.get(
            '/reports',
            Object.fromEntries(new URLSearchParams(buildQuery(nextType, next))),
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    const reset = () => {
        setFilters(emptyFilters);
        apply(type, emptyFilters);
    };

    const typeLabel = types.find((t) => t.value === type)?.label ?? 'Report';

    // Permissions disabled: all report types visible.
    // Permissions enabled: inventory requires inventory.view.
    const visibleTypes = ENABLE_PERMISSIONS
        ? types.filter((t) => t.value !== 'inventory' || can('inventory.view'))
        : types;

    const showStatus =
        type === 'appointments' || type === 'treatments' || type === 'patients';

    const formatCell = (value: string | number | undefined) =>
        typeof value === 'number' ? value.toLocaleString() : (value ?? '—');

    return (
        <>
            <Head title="Reports" />

            {/* hide app sidebar/header when printing */}
            <style>{`
                @media print {
                    [data-slot="sidebar"],
                    [data-slot="sidebar-gap"],
                    [data-slot="sidebar-container"],
                    header,
                    nav {
                        display: none !important;
                    }

                    body {
                        background: white !important;
                    }
                }
            `}</style>

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between print:hidden">
                    <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                        Reports
                    </h1>

                    {/* Permissions ENABLED: reports.download required.
                        Permissions DISABLED: always visible. */}
                    {(!ENABLE_PERMISSIONS || can('reports.download')) && (
                        <div className="flex items-center gap-3">
                            <a href={`/reports/export?${buildQuery(type, props.filters)}`}>
                                <Button variant="outline" className="gap-2">
                                    <Download className="h-4 w-4" />
                                    Export CSV (Excel)
                                </Button>
                            </a>

                            <Button
                                onClick={() => window.print()}
                                className="gap-2 bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                            >
                                <Printer className="h-4 w-4" />
                                Print / Save as PDF
                            </Button>
                        </div>
                    )}
                </div>

                {/* REPORT TYPE TABS */}
                <div className="mb-4 flex flex-wrap gap-2 print:hidden">
                    {visibleTypes.map((t) => (
                        <button
                            key={t.value}
                            type="button"
                            onClick={() => apply(t.value)}
                            className={`rounded-full border px-4 py-1.5 text-sm font-medium ${
                                t.value === type
                                    ? 'border-brand-orange bg-brand-orange text-white'
                                    : 'border-[#d1d5db] bg-white text-ink hover:border-brand-orange'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                {/* FILTERS */}
                <div className="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-[#d1d5db] bg-white p-4 print:hidden">
                    <label className="flex flex-col gap-1 text-xs font-medium text-subtle">
                        From
                        <input
                            type="date"
                            className={fieldClass}
                            value={filters.from ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, from: e.target.value || null })
                            }
                        />
                    </label>

                    <label className="flex flex-col gap-1 text-xs font-medium text-subtle">
                        To
                        <input
                            type="date"
                            className={fieldClass}
                            value={filters.to ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, to: e.target.value || null })
                            }
                        />
                    </label>

                    {showStatus && (
                        <label className="flex flex-col gap-1 text-xs font-medium text-subtle">
                            Appointment status
                            <select
                                className={fieldClass}
                                value={filters.status ?? ''}
                                onChange={(e) =>
                                    setFilters({ ...filters, status: e.target.value || null })
                                }
                            >
                                <option value="">All statuses</option>
                                {statuses.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                    )}

                    <label className="flex flex-col gap-1 text-xs font-medium text-subtle">
                        {type === 'inventory' ? 'Item name' : 'Patient name'}
                        <input
                            type="text"
                            placeholder="Search…"
                            className={fieldClass}
                            value={filters.search ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, search: e.target.value || null })
                            }
                            onKeyDown={(e) => e.key === 'Enter' && apply()}
                        />
                    </label>

                    <Button
                        onClick={() => apply()}
                        className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                    >
                        Apply
                    </Button>

                    <Button variant="outline" onClick={reset}>
                        Reset
                    </Button>

                    {type === 'inventory' && (
                        <p className="w-full text-xs text-subtle">
                            Added and Used follow the selected date range. Remaining is
                            always the current all-time stock (total added − total used).
                        </p>
                    )}
                </div>

                {/* PRINT-ONLY HEADER */}
                <div className="mb-4 hidden print:block">
                    <h1 className="text-xl font-bold">Clinic Management System</h1>
                    <h2 className="text-lg font-semibold">{typeLabel}</h2>
                    <p className="text-xs">
                        Generated: {generatedAt}
                        {props.filters.from && ` • From: ${props.filters.from}`}
                        {props.filters.to && ` • To: ${props.filters.to}`}
                        {props.filters.status && ` • Status: ${props.filters.status}`}
                        {props.filters.search && ` • Search: ${props.filters.search}`}
                    </p>
                </div>

                {/* SUMMARY */}
                <div className="mb-4 flex flex-wrap gap-3">
                    {summary.map((s) => (
                        <div
                            key={s.label}
                            className="min-w-[140px] rounded-xl border border-[#d1d5db] bg-white px-4 py-3"
                        >
                            <p className="text-xs font-medium text-subtle">{s.label}</p>
                            <p className="text-2xl font-extrabold text-ink">
                                {s.value.toLocaleString()}
                            </p>
                        </div>
                    ))}
                </div>

                {/* TABLE */}
                <div className="overflow-hidden rounded-xl border border-[#d1d5db] bg-[#ffffff]">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#d1d5db] bg-[#d1d5db] hover:bg-[#d1d5db]">
                                {columns.map((c) => (
                                    <TableHead
                                        key={c.key}
                                        className={`font-bold tracking-wide text-brand-orange-hover ${
                                            c.align === 'right' ? 'text-right' : ''
                                        }`}
                                    >
                                        {c.label}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={columns.length}
                                        className="py-10 text-center text-sm text-subtle"
                                    >
                                        No records found for the selected filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {rows.map((row, i) => (
                                <TableRow
                                    key={i}
                                    className="border-b border-[#d1d5db] last:border-0 hover:bg-[#e0e4e9]"
                                >
                                    {columns.map((c) => (
                                        <TableCell
                                            key={c.key}
                                            className={`text-ink ${
                                                c.align === 'right' ? 'text-right' : ''
                                            } ${stockStatusClass[String(row[c.key])] ?? ''}`}
                                        >
                                            {formatCell(row[c.key])}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [{ title: 'Reports', href: '/reports' }],
};