import { useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { toast, Toaster } from 'sonner';
import { UserX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { edit, deactivate, index } from '@/routes/patients';

interface Patient {
    id: number;
    full_name: string;
    gender: string;
    date_of_birth: string;
    email: string;
    contact_number: string | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_number: string | null;
    medical_history: string | null;
    allergies: string | null;
    is_active: string;
}

interface ShowProps {
    patient: Patient;
}

interface FlashProps {
    flash?: {
        message?: string;
        error?: string;
    };
}

// keep in sync with GENDER_LABELS in Create.tsx
const GENDER_LABELS: Record<string, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
};

function Field({ label, value }: { label: string; value?: string | null }) {
    return (
        <div className="space-y-1">
            <p className="text-xs font-semibold tracking-wide text-ink/60 uppercase">{label}</p>
            <p className="text-sm text-ink">{value && value.trim() !== '' ? value : '—'}</p>
        </div>
    );
}

export default function Show({ patient }: ShowProps) {
    const { flash } = usePage<FlashProps & Record<string, unknown>>().props as unknown as FlashProps;

    // Guards against React 18 Strict Mode double-invoking the effect in development.
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

    const isActive = patient.is_active === 'active';

    // Patients are soft-deactivated, never deleted.
    const handleDeactivate = () => {
        if (
            confirm(
                `Deactivate ${patient.full_name}? They will no longer be able to log in.`,
            )
        ) {
            router.put(deactivate(patient.id).url, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={`Patient — ${patient.full_name}`} />

            <Toaster richColors position="top-right" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-bold text-ink">{patient.full_name}</h1>
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
                            {patient.is_active}
                        </span>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline" className="border-[#e0d0c0]">
                            <Link href={edit(patient.id).url}>Edit</Link>
                        </Button>
                        {isActive && (
                            <Button
                                variant="outline"
                                onClick={handleDeactivate}
                                className="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-600"
                            >
                                <UserX className="h-3.5 w-3.5" />
                                Deactivate
                            </Button>
                        )}
                    </div>
                </div>

                <div className="space-y-6 rounded-xl border border-[#f0ddc8] bg-white p-5">
                    {/* Identity */}
                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Full name" value={patient.full_name} />
                        <Field label="Gender" value={GENDER_LABELS[patient.gender] ?? patient.gender} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Date of birth" value={patient.date_of_birth} />
                        <Field label="Email" value={patient.email} />
                    </div>

                    {/* Contact */}
                    <div className="grid grid-cols-2 gap-4 border-t border-[#f0ddc8] pt-4">
                        <Field label="Contact number" value={patient.contact_number} />
                        <Field label="Address" value={patient.address} />
                    </div>

                    {/* Emergency contact */}
                    <div className="grid grid-cols-2 gap-4 border-t border-[#f0ddc8] pt-4">
                        <Field label="Emergency contact name" value={patient.emergency_contact_name} />
                        <Field label="Emergency contact number" value={patient.emergency_contact_number} />
                    </div>

                    {/* Medical */}
                    <div className="space-y-4 border-t border-[#f0ddc8] pt-4">
                        <Field label="Medical history" value={patient.medical_history} />
                        <Field label="Allergies" value={patient.allergies} />
                    </div>
                </div>

                <div className="mt-4">
                    <Link href={index().url} className="text-sm text-brand-orange hover:underline">
                        ← Back to patients
                    </Link>
                </div>
            </div>
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Patients', href: '/patients' },
        { title: 'Patient Details', href: '#' },
    ],
};