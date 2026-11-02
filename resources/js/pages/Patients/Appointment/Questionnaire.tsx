import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CircleAlert } from 'lucide-react';

type Question = { key: string; label: string };

interface Props {
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    isFemale: boolean;
    appointment: { id: number } | null;
    initial: { answers: Record<string, boolean>; conditions: string[] } | null;
}

interface QuestionnaireForm {
    answers: Record<string, boolean | null>;
    conditions: string[];
    agreed: boolean;
}

function YesNo({
    value,
    onChange,
}: {
    value: boolean | null;
    onChange: (v: boolean) => void;
}) {
    const base = 'h-8 w-16 rounded-md border text-sm font-medium transition';
    const off = 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50';
    const on = 'border-blue-600 bg-blue-600 text-white';

    return (
        <div className="flex shrink-0 gap-2">
            <button
                type="button"
                aria-pressed={value === true}
                onClick={() => onChange(true)}
                className={`${base} ${value === true ? on : off}`}
            >
                Yes
            </button>
            <button
                type="button"
                aria-pressed={value === false}
                onClick={() => onChange(false)}
                className={`${base} ${value === false ? on : off}`}
            >
                No
            </button>
        </div>
    );
}

export default function Questionnaire({ sections, isFemale, appointment, initial }: Props) {
    const editing = !!appointment;

    // Start from saved answers when editing, otherwise from blanks.
    const initialAnswers: Record<string, boolean | null> = {};
    [...sections.general, ...(isFemale ? sections.women : [])].forEach((q) => {
        initialAnswers[q.key] = initial?.answers?.[q.key] ?? null;
    });

    const { data, setData, post, put, processing, errors } = useForm<QuestionnaireForm>({
        answers: initialAnswers,
        conditions: initial?.conditions ?? [],
        agreed: false,
    });

    const setAnswer = (key: string, value: boolean) =>
        setData('answers', { ...data.answers, [key]: value });

    const toggleCondition = (key: string) =>
        setData(
            'conditions',
            data.conditions.includes(key)
                ? data.conditions.filter((k) => k !== key)
                : [...data.conditions, key],
        );

    const renderQuestions = (questions: Question[]) => (
        <ul className="divide-y divide-gray-100">
            {questions.map((q) => (
                <li key={q.key} className="flex items-center justify-between gap-4 py-3">
                    <span className="text-sm text-gray-800">{q.label}</span>
                    <YesNo
                        value={data.answers[q.key] ?? null}
                        onChange={(v) => setAnswer(q.key, v)}
                    />
                </li>
            ))}
        </ul>
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (appointment) {
            put(`/patient/appointments/${appointment.id}/questionnaire`);
        } else {
            post('/patient/appointments/questionnaire');
        }
    };

    // Turn the raw validation messages into plain sentences.
    const errorMessages = Object.entries(errors).map(([key, message]) => {
        if (key === 'agreed') {
            return 'Please tick the box confirming your answers are true and correct.';
        }
        return message as string;
    });

    return (
        <>
            <Head title={editing ? 'Edit Questionnaire' : 'Medical Questionnaire'} />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-ink">
                        {editing ? 'Edit Medical Questionnaire' : 'Medical Questionnaire'}
                    </h1>
                    <p className="mt-1 text-sm text-subtle">
                        {editing
                            ? 'Update your answers. This changes your medical record.'
                            : 'Please answer before booking your appointment.'}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {errorMessages.length > 0 && (
                        <Alert className="border-red-200 bg-red-50 text-red-700">
                            <CircleAlert className="text-red-600" />
                            <AlertTitle className="text-red-700">Something's not right</AlertTitle>
                            <AlertDescription className="text-red-700">
                                <ul className="list-inside list-disc text-sm text-red-700">
                                    {errorMessages.map((message, i) => (
                                        <li key={i}>{message}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

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
                            <CardTitle className="text-base">
                                Conditions you have had or have now
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-x-6 gap-y-2 sm:grid-cols-2 md:grid-cols-3">
                                {sections.conditions.map((c) => (
                                    <label
                                        key={c.key}
                                        className="flex cursor-pointer items-center gap-2 text-sm text-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={data.conditions.includes(c.key)}
                                            onChange={() => toggleCondition(c.key)}
                                            className="h-4 w-4 rounded border-gray-300"
                                        />
                                        {c.label}
                                    </label>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <label className="flex cursor-pointer items-start gap-3 text-sm text-gray-800">
                        <input
                            type="checkbox"
                            checked={data.agreed}
                            onChange={(e) => setData('agreed', e.target.checked)}
                            className="mt-0.5 h-4 w-4 rounded border-gray-300"
                        />
                        <span>
                            To the best of my knowledge, all of the preceding answers are true and correct.
                        </span>
                    </label>

                    <div className="flex justify-end gap-2 border-t border-gray-200 pt-5">
                        {editing && (
                            <Button asChild variant="outline">
                                <Link href="/patient/appointments">Cancel</Link>
                            </Button>
                        )}
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-10 bg-blue-600 px-6 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {editing ? 'Save Questionnaire' : 'Continue to Booking'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Questionnaire.layout = {
    breadcrumbs: [
        { title: 'My Appointments', href: '/patient/appointments' },
        { title: 'Questionnaire', href: '/patient/appointments/questionnaire' },
    ],
};