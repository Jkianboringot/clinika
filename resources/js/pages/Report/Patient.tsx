import { Head, usePage } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

const CLINIC = {
    name: 'MB Klinika',
    address: '50-B Salvacion St. Virac Catanduanes',
    contact: 'Tel: 0900-000-0000  •  Email: info@yourclinic.com',
};

interface PageProps {
    patient: Record<string, string | number | null>;
    appointments: {
        date: string;
        time: string;
        procedure: string;
        staff: string;
        status: string;
    }[];
    treatments: {
        date: string;
        procedure: string;
        doctor: string;
        duration: number;
        summary: string;
        items: string;
        follow_up: string;
    }[];
    summary: { label: string; value: string | number }[];
    generatedAt: string;
}

type Val = string | number | null | undefined;

const show = (x: Val) =>
    x === null || x === undefined || x === '' ? '—' : String(x);

// Report-style field: small caps label, value on a rule line
function Field({ label, value }: { label: string; value: Val }) {
    return (
        <div className="min-w-0">
            <p className="text-[10px] font-semibold uppercase tracking-wider text-subtle">
                {label}
            </p>
            <p className="mt-0.5 break-words border-b border-[#d1d5db] pb-1 text-sm font-medium text-ink">
                {show(value)}
            </p>
        </div>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="avoid-break mb-5">
            <h2 className="mb-3 border-b-2 border-brand-orange pb-1 text-xs font-extrabold uppercase tracking-widest text-ink">
                {title}
            </h2>
            {children}
        </section>
    );
}

function DataTable({
    heads,
    children,
    empty,
}: {
    heads: string[];
    children: ReactNode;
    empty?: string;
}) {
    return (
        <table className="data-table w-full border-collapse text-xs">
            <thead>
                <tr className="bg-[#f3f4f6]">
                    {heads.map((h) => (
                        <th
                            key={h}
                            className="border border-[#d1d5db] px-2 py-1.5 text-left font-bold text-ink"
                        >
                            {h}
                        </th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {empty ? (
                    <tr>
                        <td
                            colSpan={heads.length}
                            className="border border-[#d1d5db] px-2 py-4 text-center text-subtle"
                        >
                            {empty}
                        </td>
                    </tr>
                ) : (
                    children
                )}
            </tbody>
        </table>
    );
}

const td = 'border border-[#d1d5db] px-2 py-1.5 align-top text-ink';

export default function Patient() {
    const { patient, appointments, treatments, summary, generatedAt } =
        usePage().props as unknown as PageProps;

    // Auto-open print dialog when opened from the PDF button (?print=1)
    useEffect(() => {
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            const t = setTimeout(() => window.print(), 500);
            return () => clearTimeout(t);
        }
    }, []);

    return (
        <>
            <Head title={`Patient Report - ${patient.full_name}`} />

            <style>{`
                .report-layout { width: 100%; border-collapse: collapse; }
                .print-header-group, .print-footer-group,
                .print-header, .print-footer { display: none; }

                @media print {
                    @page { size: A4; margin: 12mm 12mm 14mm 12mm; }

                    [data-slot="sidebar"], [data-slot="sidebar-gap"],
                    [data-slot="sidebar-container"], header, nav {
                        display: none !important;
                    }
                    body { background: white !important; }

                    .report-layout > tbody > tr > td { padding: 0 !important; }
                    .print-header-group { display: table-header-group !important; }
                    .print-footer-group { display: table-footer-group !important; }

                    .print-header {
                        display: flex !important; align-items: center; gap: 12px;
                        padding-bottom: 8px; margin-bottom: 12px;
                        border-bottom: 2px solid #f97316; color: #111827;
                    }
                    .print-footer {
                        display: flex !important; justify-content: space-between;
                        align-items: center; padding-top: 6px; margin-top: 12px;
                        border-top: 1px solid #d1d5db; font-size: 9px; color: #6b7280;
                    }
                    .avoid-break { break-inside: avoid; }
                    tr { break-inside: avoid; }
                    .data-table thead { display: table-header-group; }
                }
            `}</style>

            <table className="report-layout">
                {/* PRINT HEADER (repeats every page) */}
                <thead className="print-header-group">
                    <tr>
                        <td>
                            <div className="print-header">
                                <img
                                    src="/logo.png"
                                    alt="Klinika"
                                    className="h-16 w-16 shrink-0 object-contain"
                                />
                                <div className="flex-1">
                                    <h1 className="text-base font-extrabold tracking-tight">
                                        {CLINIC.name}
                                    </h1>
                                    <p className="text-[10px] leading-tight">{CLINIC.address}</p>
                                    <p className="text-[10px] leading-tight">{CLINIC.contact}</p>
                                </div>
                                <div className="text-right">
                                    <h2 className="text-sm font-bold">Patient Report</h2>
                                    <p className="text-[9px] leading-tight">
                                        {String(patient.full_name)}
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>

                {/* PRINT FOOTER (repeats every page) */}
                <tfoot className="print-footer-group">
                    <tr>
                        <td>
                            <div className="print-footer">
                                <span>{CLINIC.name} • Confidential, for clinic use only</span>
                                <span>
                                    Patient Report • Generated: {generatedAt}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>

                <tbody>
                    <tr>
                        <td>
                            <div className="mx-auto w-full max-w-4xl p-6 print:max-w-none print:p-0">
                                {/* screen-only top bar */}
                                <div className="mb-4 flex items-center justify-between print:hidden">
                                    <h1 className="text-2xl font-bold text-ink">Patient Report</h1>
                                    <Button
                                        onClick={() => window.print()}
                                        className="gap-2 bg-brand-orange font-bold text-white hover:bg-brand-orange-hover"
                                    >
                                        <Printer className="h-4 w-4" />
                                        Print / Save as PDF
                                    </Button>
                                </div>

                                <div className="rounded-lg bg-white p-6 print:rounded-none print:p-0">
                                    {/* Report title */}
                                    <div className="mb-5 text-center">
                                        <h1 className="text-lg font-extrabold uppercase tracking-widest text-ink">
                                            Patient Medical Report
                                        </h1>
                                        <p className="mt-1 text-sm font-semibold text-ink">
                                            {String(patient.full_name)}
                                        </p>
                                    </div>

                                    {/* Summary strip */}
                                    <div className="avoid-break mb-5 grid grid-cols-3 divide-x divide-[#d1d5db] rounded border border-[#d1d5db] bg-[#f9fafb]">
                                        {summary.map((s) => (
                                            <div key={s.label} className="px-3 py-2 text-center">
                                                <p className="text-[10px] font-semibold uppercase tracking-wider text-subtle">
                                                    {s.label}
                                                </p>
                                                <p className="text-base font-extrabold text-ink">
                                                    {typeof s.value === 'number'
                                                        ? s.value.toLocaleString()
                                                        : s.value}
                                                </p>
                                            </div>
                                        ))}
                                    </div>

                                    {/* PATIENT INFORMATION */}
                                    <Section title="Patient Information">
                                        <div className="space-y-3">
                                            <div className="grid grid-cols-3 gap-4">
                                                <Field label="First name" value={patient.first_name} />
                                                <Field label="Middle name" value={patient.middle_name} />
                                                <Field label="Last name" value={patient.last_name} />
                                            </div>
                                            <div className="grid grid-cols-3 gap-4">
                                                <Field label="Gender" value={patient.gender} />
                                                <Field label="Date of birth" value={patient.date_of_birth} />
                                                <Field label="Age" value={patient.age} />
                                            </div>
                                        </div>
                                    </Section>

                                    {/* CONTACT */}
                                    <Section title="Contact Information">
                                        <div className="space-y-3">
                                            <div className="grid grid-cols-2 gap-4">
                                                <Field label="Email" value={patient.email} />
                                                <Field label="Contact number" value={patient.contact_number} />
                                            </div>
                                            <Field label="Address" value={patient.address} />
                                        </div>
                                    </Section>

                                    {/* EMERGENCY */}
                                    <Section title="Emergency Contact">
                                        <div className="grid grid-cols-2 gap-4">
                                            <Field
                                                label="Emergency contact name"
                                                value={patient.emergency_contact_name}
                                            />
                                            <Field
                                                label="Emergency contact number"
                                                value={patient.emergency_contact_number}
                                            />
                                        </div>
                                    </Section>

                                    {/* MEDICAL */}
                                    <Section title="Medical Information">
                                        <div className="space-y-3">
                                            <Field label="Medical history" value={patient.medical_history} />
                                            <Field label="Allergies" value={patient.allergies} />
                                            <Field label="Patient status" value={patient.status} />
                                        </div>
                                    </Section>

                                    {/* APPOINTMENTS */}
                                    <Section title="Appointment History">
                                        <DataTable
                                            heads={['Date', 'Time', 'Procedure', 'Staff', 'Status']}
                                            empty={appointments.length === 0 ? 'No appointments.' : undefined}
                                        >
                                            {appointments.map((a, i) => (
                                                <tr key={i}>
                                                    <td className={td}>{a.date}</td>
                                                    <td className={td}>{a.time}</td>
                                                    <td className={td}>{a.procedure}</td>
                                                    <td className={td}>{a.staff}</td>
                                                    <td className={td}>{a.status}</td>
                                                </tr>
                                            ))}
                                        </DataTable>
                                    </Section>

                                    {/* TREATMENTS */}
                                    <Section title="Treatment History">
                                        <DataTable
                                            heads={['Date', 'Procedure', 'Doctor', 'Min', 'Summary', 'Items Used', 'Follow-up']}
                                            empty={treatments.length === 0 ? 'No treatments.' : undefined}
                                        >
                                            {treatments.map((t, i) => (
                                                <tr key={i}>
                                                    <td className={td}>{t.date}</td>
                                                    <td className={td}>{t.procedure}</td>
                                                    <td className={td}>{t.doctor}</td>
                                                    <td className={td}>{t.duration}</td>
                                                    <td className={td}>{t.summary}</td>
                                                    <td className={td}>{t.items}</td>
                                                    <td className={td}>{t.follow_up}</td>
                                                </tr>
                                            ))}
                                        </DataTable>
                                    </Section>

                                    {/* Signature */}
                                    <div className="avoid-break mt-10 flex justify-end">
                                        <div className="w-60 text-center">
                                            <div className="border-b border-ink" />
                                            <p className="mt-1 text-[10px] uppercase tracking-wider text-subtle">
                                                Attending physician signature
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </>
    );
}

Patient.layout = {
    breadcrumbs: [
        { title: 'Reports', href: '/reports' },
        { title: 'Patient Report', href: '#' },
    ],
};