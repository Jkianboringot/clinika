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
    UserCheck,
    UserX,
} from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, deactivate, activate, index as usersIndex } from '@/routes/users';

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: string;
    created_at: string;
}

// Patients are their own model/table (see Patient.php), not a User with a
// "patient" role. The backend aliases `full_name as name` so this shape
// lines up with UserRow on `name`/`email`/`is_active`/`created_at` — the only
// real difference is the extra contact_number.
interface PatientRow {
    id: number;
    name: string;
    email: string;
    contact_number: string | null;
    is_active: string;
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

function formatDate(value: string | null) {
    if (!value) return '—';

    const date = new Date(value);
    if (isNaN(date.getTime())) return value;

    const month = date.toLocaleString('en-US', { month: 'short' });
    const day = date.getDate();
    const year = date.getFullYear();

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
    // Split into two statements to avoid a @babel/parser TSX ambiguity with
    // multi-line generics followed by `()`.
    const page = usePage<PageProps & Record<string, unknown>>();
    const { flash, users, filters, roleCounts, activeRole } = page.props as unknown as PageProps;

    // `put` deactivates/activates users
    // (PUT /users/{user}/deactivate, PUT /users/{user}/activate).
    const { processing, put } = useForm();
    const [search, setSearch] = useState(filters?.search ?? '');
    const isFirstRender = useRef(true);

    // Guards against React Strict Mode double-invoking this effect on mount
    // in development, which would fire the toast twice.
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
    const isPatientTab = activeRole === 'patient';
    const columnCount = isPatientTab ? 6 : 5;

    // Debounced: typing only updates local state immediately. The actual
    // request waits until 400ms after the user stops typing.
    const debounceTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // When a tab switch also clears `search`, skip the debounce effect once
    // so a stale request for the OLD role can't clobber the tab switch.
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

    // Tab switches are immediate, not debounced, and reset the search box.
    const handleTabChange = (role: Role) => {
        if (role === activeRole) return;

        if (debounceTimeout.current) clearTimeout(debounceTimeout.current);
        skipNextSearchEffect.current = true;

        setSearch('');
        router.get(
            usersIndex().url,
            { role },
            { preserveState: true, replace: true },
        );
    };

    // Users are soft-deactivated, never deleted. Patients are read-only
    // here (status badge only); they're managed from the Patients module.
    const handleDeactivate = (row: Row) => {
        if (isPatientRow(row)) return;
        if (!confirm(`Deactivate "${row.name}"? They will no longer be able to log in.`)) return;

        put(deactivate(row.id).url);
    };

    // Reactivation only applies to currently deactivated users. The backend
    // enforces this too, so a stale page can't activate an active user.
    const handleActivate = (row: Row) => {
        if (isPatientRow(row) || row.is_active === 'active') return;
        if (!confirm(`Activate "${row.name}"? They will be able to log in again.`)) return;

        put(activate(row.id).url);
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
                            className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                                activeRole === tab.key
                                    ? 'bg-white text-ink shadow-sm'
                                    : 'text-subtle hover:text-ink'
                            }`}
                        >
                            {tab.label} ({roleCounts?.[tab.key] ?? 0})
                        </button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-xl border border-[#c9c0b7] bg-white">
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
                                {isPatientTab && (
                                    <TableHead className="font-bold text-brand-orange-hover">Contact</TableHead>
                                )}
                                <TableHead className="font-bold text-brand-orange-hover">Status</TableHead>
                                <TableHead className="font-bold text-brand-orange-hover">Joined</TableHead>
                                <TableHead className="text-right font-bold text-brand-orange-hover">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {userRows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={columnCount} className="py-10 text-center text-sm text-subtle">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {userRows.map((row) => {
                                const patientRow = isPatientRow(row);
                                const isActive = row.is_active === 'active';

                                return (
                                    <TableRow
                                        key={row.id}
                                        className="border-b border-[#c9c0b7] last:border-0"
                                    >
                                        <TableCell>{row.name}</TableCell>
                                        <TableCell>{row.email}</TableCell>
                                        {patientRow && (
                                            <TableCell>{row.contact_number ?? '—'}</TableCell>
                                        )}
                                        <TableCell>
                                            <span
                                                className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${
                                                    isActive
                                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                        : 'bg-red-50 text-red-700 ring-red-200'
                                                }`}
                                            >
                                                <span
                                                    className={`h-1.5 w-1.5 rounded-full ${
                                                        isActive ? 'bg-emerald-500' : 'bg-red-500'
                                                    }`}
                                                />
                                                {row.is_active}
                                            </span>
                                        </TableCell>
                                        <TableCell>{formatDate(row.created_at)}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-2">
                                                {!patientRow && (
                                                    <Link
                                                        href={edit(row.id).url}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                        Edit
                                                    </Link>
                                                )}
                                                {!patientRow && isActive && (
                                                    <button
                                                        type="button"
                                                        disabled={processing}
                                                        onClick={() => handleDeactivate(row)}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50"
                                                    >
                                                        <UserX className="h-3.5 w-3.5" />
                                                        Deactivate
                                                    </button>
                                                )}
                                                {!patientRow && !isActive && (
                                                    <button
                                                        type="button"
                                                        disabled={processing}
                                                        onClick={() => handleActivate(row)}
                                                        className="inline-flex items-center gap-1.5 rounded-md border border-emerald-200 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                                                    >
                                                        <UserCheck className="h-3.5 w-3.5" />
                                                        Activate
                                                    </button>
                                                )}
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
                                    className={`flex items-center rounded-md px-3 py-1 text-sm ${
                                        link.active
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