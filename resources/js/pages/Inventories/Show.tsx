import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { edit, destroy, index } from '@/routes/patients';

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
}

interface ShowProps {
    patient: Patient;
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
    const handleDelete = () => {
        router.delete(destroy(patient.id).url);
    };

    return (
        <>
            <Head title={`Patient — ${patient.full_name}`} />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-ink">{patient.full_name}</h1>
                    <div className="flex gap-2">
                        <Button asChild variant="outline" className="border-[#e0d0c0]">
                            <Link href={edit(patient.id).url}>Edit</Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (confirm(`Delete ${patient.full_name}'s record? This can't be undone.`)) {
                                    handleDelete();
                                }
                            }}
                        >
                            Delete
                        </Button>
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