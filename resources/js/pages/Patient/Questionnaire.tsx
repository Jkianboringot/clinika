import { Head, router, useForm } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Question = { key: string; label: string };

interface Props {
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    isFemale: boolean;
    /** Staff flow before a patient is chosen: women questions can be left blank. */
    womenOptional?: boolean;
    saved: { answers: Record<string, boolean>; conditions: string[] } | null;
    postUrl?: string;
    skipUrl?: string;
    canSkip?: boolean;
}

interface QuestionnaireForm {
    answers: Record<string, boolean | null>;
    conditions: string[];
    agreed: boolean;
}

function YesNo({
    value,
    onChange,
    invalid,
}: {
    value: boolean | null;
    onChange: (value: boolean) => void;
    invalid?: boolean;
}) {
    const base = 'h-8 w-16 rounded-md border text-sm font-medium transition';
    const off = invalid
        ? 'border-red-300 bg-white text-gray-700 hover:bg-gray-50'
        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50';
    const on = 'border-blue-600 bg-blue-600 text-white';

    return (
        <div className="flex shrink-0 gap-2">
            <button type="button" aria-pressed={value === true} onClick={() => onChange(true)} className={`${base} ${value === true ? on : off}`}>
                Yes
            </button>
            <button type="button" aria-pressed={value === false} onClick={() => onChange(false)} className={`${base} ${value === false ? on : off}`}>
                No
            </button>
        </div>
    );
}

export default function PatientQuestionnaire({
    sections,
    isFemale,
    womenOptional = false,
    saved,
    postUrl,
    skipUrl,
    canSkip,
}: Props) {
    const generalKeys = sections.general.map((q) => q.key);
    const womenKeys = isFemale ? sections.women.map((q) => q.key) : [];

    // Every question that has a Yes/No button on screen.
    const questionKeys = [...generalKeys, ...womenKeys];

    // Questions that must be answered. Optional women questions are not counted.
    const requiredKeys = womenOptional ? generalKeys : questionKeys;

    const { data, setData, post, processing, errors, transform } = useForm<QuestionnaireForm>({
        answers: Object.fromEntries(questionKeys.map((key) => [key, saved?.answers?.[key] ?? null])),
        conditions: saved?.conditions ?? [],
        agreed: false,
    });

    // Unanswered (null) questions are not sent, so optional ones stay blank.
    transform((d) => ({
        ...d,
        answers: Object.fromEntries(Object.entries(d.answers).filter(([, v]) => v !== null)),
    }));

    const setAnswer = (key: string, value: boolean) =>
        setData('answers', { ...data.answers, [key]: value });

    const toggleCondition = (key: string) =>
        setData(
            'conditions',
            data.conditions.includes(key) ? data.conditions.filter((k) => k !== key) : [...data.conditions, key],
        );

    const answeredCount = requiredKeys.filter((k) => data.answers[k] !== null && data.answers[k] !== undefined).length;

    const fieldErrors = errors as Record<string, string>;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(postUrl ?? '/patient/questionnaire');
    };

    const renderQuestions = (questions: Question[]) => (
        <ul className="divide-y divide-gray-100">
            {questions.map((q) => {
                const invalid = Boolean(fieldErrors[`answers.${q.key}`]);

                return (
                    <li key={q.key} className="flex items-center justify-between gap-4 py-3">
                        <span className={`text-sm ${invalid ? 'text-red-600' : 'text-gray-800'}`}>{q.label}</span>
                        <YesNo value={data.answers[q.key] ?? null} onChange={(v) => setAnswer(q.key, v)} invalid={invalid} />
                    </li>
                );
            })}
        </ul>
    );

    return (
        <>
            <Head title="Medical Questionnaire" />

            <div className="mx-auto w-full max-w-3xl p-4">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold">Medical Questionnaire</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Please answer this before booking an appointment. Your dentist will see your answers before any treatment.
                        {saved && ' You can update your answers any time your health changes.'}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                Please answer every question and confirm the statement at the bottom.
                            </AlertDescription>
                        </Alert>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                <span>Answer the following questions by Yes or No</span>
                                <span className="text-xs font-normal text-muted-foreground">
                                    {answeredCount} / {requiredKeys.length} answered
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>{renderQuestions(sections.general)}</CardContent>
                    </Card>

                    {isFemale && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Women
                                    {womenOptional && (
                                        <span className="ml-2 text-xs font-normal text-muted-foreground">
                                            Answer only if the patient is female
                                        </span>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>{renderQuestions(sections.women)}</CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Check if any of the following which you have had or have at the present
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-x-6 gap-y-2 sm:grid-cols-2 md:grid-cols-3">
                                {sections.conditions.map((c) => (
                                    <label key={c.key} className="flex cursor-pointer items-center gap-2 text-sm text-gray-800">
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

                    <Card>
                        <CardContent className="pt-6">
                            <label className="flex cursor-pointer items-start gap-3 text-sm text-gray-800">
                                <input
                                    type="checkbox"
                                    checked={data.agreed}
                                    onChange={(e) => setData('agreed', e.target.checked)}
                                    className="mt-0.5 h-4 w-4 rounded border-gray-300"
                                />
                                <span>
                                    To the best of my knowledge, all of the preceding answers are true and correct. If I ever
                                    have any change in my health or if my medicine change, I will inform the doctor of dentistry
                                    at the next appointment without fail.
                                </span>
                            </label>
                            {errors.agreed && <p className="mt-2 text-sm text-red-600">{errors.agreed}</p>}

                            {canSkip && skipUrl && (
                                <div className="mt-5 flex justify-start border-t pt-4">
                                    <Button type="button" variant="outline" onClick={() => router.post(skipUrl)}>
                                        Skip (all No)
                                    </Button>
                                </div>
                            )}

                            <div className="mt-5 flex justify-end border-t pt-4">
                                <Button type="submit" disabled={processing}>
                                    {saved ? 'Update questionnaire' : 'Save questionnaire'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </>
    );
}

PatientQuestionnaire.layout = {
    breadcrumbs: [
        {
            title: 'Medical Questionnaire',
            href: '/patient/questionnaire',
        },
    ],
};