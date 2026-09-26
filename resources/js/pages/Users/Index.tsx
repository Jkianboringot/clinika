import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast, Toaster } from 'sonner';
import {
    ChevronLeft,
    ChevronRight,
    Pencil,
    Search,
    Trash2,
} from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, destroy, edit, index as usersIndex } from '@/routes/users';
import { destroy as destroyPatient, edit as editPatient } from '@/routes/patients';

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: string;
    created_at: string;
}

// Patients are their own model/table (see Patient.php), not a User with a
// "patient" role. The backend aliases `full_name as name` so this shape
// lines up with UserRow on `name`/`email`/`created_at` — the only real
// difference is contact_number instead of is_active.
interface PatientRow {
    id: number;
    name: string;
    email: string;
    contact_number: string | null;
    created_at: string;
}

type Row = UserRow | PatientRow;

function isPatientRow(row: Row): row is PatientRow {
    return 'contact_number' in row;
}

interface PaginatedRows<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

// Keys must match the role slugs used by self::MANAGEABLE_ROLES on the backend.
type Role = 'dentist' | 'staff' | 'patient';

interface PageProps {
    flash: {
        message?: string;
        error?: string;
    };
    users: PaginatedRows<Row>;
    filters: { search?: string; role?: Role };
    roleCounts: Record<Role, number>;
    activeRole: Role;
}

const TABS: { key: Role; label: string }[] = [
    { key: 'dentist', label: 'Dentists' },
    { key: 'staff', label: 'Staff' },
    { key: 'patient', label: 'Patients' },
];

const TAB_TITLES: Record<Role, string> = {
    dentist: 'Dentist Accounts',
    staff: 'Staff Accounts',
    patient: 'Patient Accounts',
};

function formatId(id: number) {
    return `D${String(id).padStart(3, '0')}`;
}

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' }); // "Sep"
    const day = date.getDate();                                     // 25
    const year = date.getFullYear();                                // 2026

    return `${month} ${day}, ${year}`; // e.g. "Sep 25, 2026"
}

// Laravel's paginator labels are always one of these three shapes —
// render icons for prev/next instead of trusting raw HTML entities.
function paginationLabel(label: string) {
    if (label.includes('Previous')) return <ChevronLeft className="h-4 w-4" />;
    if (label.includes('Next')) return <ChevronRight className="h-4 w-4" />;
    return label;
}

export default function Index() {
    // NOTE: split from the previous single-statement
    //   `usePage<PageProps & Record<string, unknown>>().props as unknown as PageProps`
    // because some @babel/parser TSX versions fail to parse a multi-line
    // generic argument list immediately followed by `()` (ambiguous with
    // JSX). Splitting into two statements avoids the parser ambiguity.
    const page = usePage<PageProps & Record<string, unknown>>();
    const { flash, users, filters, roleCounts, activeRole } = page.props as unknown as PageProps;

    const { processing, delete: destroyForm } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');
    const isFirstRender = useRef(true);

    // Guards against React 18 Strict Mode (enabled via createInertiaApp's
    // `strictMode: true`) double-invoking this effect on mount in
    // development, which was firing the toast twice. Tracking the last
    // flash value we actually showed makes the second invocation a no-op.
    // Doesn't affect production, where effects only run once anyway.
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

    const userRows = users?.data ?? [];

    // Debounced: typing only updates local state immediately. The actual
    // request to the server waits until 400ms after the user stops typing,
    // so it doesn't fire on every keystroke — only once they pause.
    const debounceTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // When a tab switch also clears `search` (see handleTabChange), we don't
    // want the debounce effect below to fire a *second*, stale request for
    // the OLD role a moment later — that race was clobbering tab switches
    // and made clicking Staff/Patient look broken/reverted. This flag tells
    // the effect to skip once when the reset came from a tab change.
    const skipNextSearchEffect = useRef(false);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        if (skipNextSearchEffect.current) {
            skipNextSearchEffect.current = false;
            return;
        }

        debounceTimeout.current = setTimeout(() => {
            router.get(
                usersIndex().url,
                { search, role: activeRole },
                { preserveState: true, replace: true },
            );
        }, 400);

        return () => {
            if (debounceTimeout.current) clearTimeout(debounceTimeout.current);
        };
    }, [search, activeRole]);

    // Tab switches are immediate, not debounced, and reset the search box
    // since "Dr." matches from the Dentists tab don't mean much on Staff.
    const handleTabChange = (role: Role) => {
        if (role === activeRole) return;

        // Cancel any pending debounced search request and suppress the
        // effect that setSearch('') below would otherwise trigger, so the
        // tab switch can't be overwritten by a stale request for the old role.
        if (debounceTimeout.current) clearTimeout(debounceTimeout.current);
        skipNextSearchEffect.current = true;

        setSearch('');
        router.get(
            usersIndex().url,
            { role },
            { preserveState: true, replace: true },
        );
    };

    const handleDelete = (row: Row) => {
        if (!confirm(`Delete "${row.name}"? This can't be undone.`)) return;

        // Patients live in a separate table/resource, so they need their own
        // destroy route rather than the users one.
        if (isPatientRow(row)) {
            destroyForm(destroyPatient(row.id).url);
        } else {
            destroyForm(destroy(row.id).url);
        }
    };

    return (
        <>
            <Head title="Users" />

            <Toaster richColors position="top-right" />

            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-extrabold tracking-tight text-ink">
                            Users
                        </h1>
                      
                    </div>
                    <Link href={create().url}>
                        <Button className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover">
                            New user
                        </Button>
                    </Link>
                </div>

                <div className="mb-5 inline-flex gap-1 rounded-full bg-[#e9e4dc] p-1">
                    {TABS.map((tab) => (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => handleTabChange(tab.key)}
                            className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${activeRole === tab.key
                                    ? 'bg-white text-ink shadow-sm'
                                    : 'text-subtle hover:text-ink'
                                }`}
                        >
                            {tab.label} ({roleCounts?.[tab.key] ?? 0})
                        </button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-xl border border-[#c9c0b7]">
                    <div className="flex items-center justify-between border-b border-[#c9c0b7] px-5 py-3">
                        <h2 className="font-bold text-ink">{TAB_TITLES[activeRole]}</h2>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-orange" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={`Search ${TABS.find((t) => t.key === activeRole)?.label.toLowerCase()}...`}
                                maxLength={100}
                                className="w-56 border-brand-orange/40 bg-white pl-9 text-sm"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-[#c9c0b7]">
                                <TableHead className="font-bold text-brand-orange-hover">Name</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Email</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">
                                    {activeRole === 'patient' ? 'Contact' : 'Status'}
                                </TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Joined</TableHead>
                                <TableHead className="text-right font-bold text-brand-orange-hover">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {userRows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-10 text-center text-sm text-subtle">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {userRows.map((row) => {
                                const patientRow = isPatientRow(row);

                                return (
                                    <TableRow
                                        key={row.id}
                                        className="border-b border-[#c9c0b7] last:border-0"
                                    >
                                        <TableCell>
                                            {row.name}
                                        </TableCell>
                                        <TableCell>{row.email}</TableCell>
                                        <TableCell>
                                            {patientRow ? (
                                                row.contact_number ?? '—'
                                            ) : (
                                                <span
                                                    className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium text-white ${row.is_active === 'Active'
                                                        ? 'bg-emerald-600'
                                                        : 'bg-slate-400'
                                                        }`}
                                                >
                                                    {row.is_active}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(row.created_at)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-4">
                                                <Link
                                                    href={patientRow ? editPatient(row.id).url : edit(row.id).url}
                                                    className="flex items-center gap-1 text-sm font-medium text-ink hover:text-brand-orange"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    disabled={processing}
                                                    onClick={() => handleDelete(row)}
                                                    className="flex items-center gap-1 text-sm font-medium text-ink hover:text-danger disabled:opacity-50"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    Delete
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    {users?.links?.length > 3 && (
                        <div className="flex gap-1 border-t border-[#c9c0b7] px-5 py-3">
                            {users.links.map((link, i) => (
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
            title: 'User',
            href: '/users',
        },
    ],
};