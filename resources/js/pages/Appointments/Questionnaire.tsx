import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Check, ClipboardList } from 'lucide-react';
import { index } from '@/routes/appointments';

type Question = { key: string; label: string };

interface Props {
    patient: { id: number; full_name: string };
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    isFemale: boolean;
    questionnaire: {
        answers: Record<string, boolean | null>;
        conditions: string[];
        updated_at?: string | null;
    } | null;
}

// Read-only Yes / No badge (same look as the edit page buttons, but not clickable).
function Answer({ value }: { value: boolean | null | undefined }) {
    const base =
        'inline-flex h-8 w-16 shrink-0 items-center justify-center rounded-md border text-sm font-medium';

    if (value === true) {
        return <span className={`${base} border-blue-600 bg-blue-600 text-white`}>Yes</span>;
    }
    if (value === false) {
        return <span className={`${base} border-gray-300 bg-gray-100 text-gray-700`}>No</span>;
    }
    return <span className={`${base} border-dashed border-gray-200 text-gray-400`}>—</span>;
}

export default function Questionnaire({ patient, sections, isFemale, questionnaire }: Props) {
    const answers = questionnaire?.answers ?? {};
    const conditions = questionnaire?.conditions ?? [];

    const selectedConditions = sections.conditions.filter((c) => conditions.includes(c.key));

    const renderQuestions = (questions: Question[]) => (
        <ul className="divide-y divide-gray-100">
            {questions.map((q) => (
                <li key={q.key} className="flex items-center justify-between gap-4 py-3">
                    <span className="text-sm text-gray-800">{q.label}</span>
                    <Answer value={answers[q.key]} />
                </li>
            ))}
        </ul>
    );

    return (
        <>
            <Head title="Medical Questionnaire" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-ink">Medical Questionnaire</h1>
                        <p className="mt-1 text-sm text-subtle">
                            {patient.full_name}
                            {questionnaire?.updated_at && ` · Last updated ${questionnaire.updated_at}`}
                        </p>
                    </div>
                    <Link
                        href={index().url}
                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" />
                        Back to appointments
                    </Link>
                </div>

                {!questionnaire ? (
                    <div className="flex flex-col items-center gap-2 rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
                        <ClipboardList className="h-6 w-6 text-gray-400" />
                        <p className="text-sm text-subtle">
                            No questionnaire on file for this patient yet.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Medical Questionnaire (Yes / No)</CardTitle>
                            </CardHeader>
                            <CardContent>{renderQuestions(sections.general)}</CardContent>
                        </Card>

                        {isFemale && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Women</CardTitle>
                                </CardHeader>
                                <CardContent>{renderQuestions(sections.women)}</CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between gap-3">
                                    <CardTitle className="text-base">
                                        Conditions the patient has had or has now
                                    </CardTitle>
                                    <span
                                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                            selectedConditions.length > 0
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        }`}
                                    >
                                        {selectedConditions.length} reported
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Quick summary of what the patient reported */}
                                {selectedConditions.length > 0 ? (
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-700">
                                            Reported conditions
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {selectedConditions.map((c) => (
                                                <span
                                                    key={c.key}
                                                    className="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-3 py-1 text-sm font-semibold text-white"
                                                >
                                                    <Check className="h-3.5 w-3.5" strokeWidth={3} />
                                                    {c.label}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ) : (
                                    <p className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">
                                        No conditions reported.
                                    </p>
                                )}

                                {/* Full list: ticked ones stand out, the rest stay muted */}
                                <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                                    {sections.conditions.map((c) => {
                                        const active = conditions.includes(c.key);

                                        return (
                                            <div
                                                key={c.key}
                                                className={`flex items-center gap-2.5 rounded-md border px-3 py-2 text-sm ${
                                                    active
                                                        ? 'border-blue-600 bg-blue-50 font-semibold text-blue-800'
                                                        : 'border-gray-200 text-gray-400'
                                                }`}
                                            >
                                                <span
                                                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full ${
                                                        active
                                                            ? 'bg-blue-600 text-white'
                                                            : 'border border-gray-300'
                                                    }`}
                                                >
                                                    {active && <Check className="h-3 w-3" strokeWidth={3} />}
                                                </span>
                                                <span>{c.label}</span>
                                                <span className="sr-only">{active ? '(reported)' : '(not reported)'}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

Questionnaire.layout = {
    breadcrumbs: [
        { title: 'Appointments', href: '/appointments' },
        { title: 'Medical Questionnaire', href: '#' },
    ],
};