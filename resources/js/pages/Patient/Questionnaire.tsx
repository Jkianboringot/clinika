import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Question = { key: string; label: string };

interface Props {
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    isFemale: boolean;
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
    clearable,
}: {
    value: boolean | null;
    onChange: (value: boolean | null) => void;
    invalid?: boolean;
    /** Optional question: clicking the selected button again clears it. */
    clearable?: boolean;
}) {
    const base = 'h-8 w-16 rounded-md border text-sm font-medium transition';
    const off = invalid
        ? 'border-red-300 bg-white text-gray-700 hover:bg-gray-50'
        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50';
    const on = 'border-blue-600 bg-blue-600 text-white';

    const pick = (next: boolean) => onChange(clearable && value === next ? null : next);

    return (
        <div className="flex shrink-0 gap-2">
            <button type="button" aria-pressed={value === true} onClick={() => pick(true)} className={`${base} ${value === true ? on : off}`}>
                Yes
            </button>
            <button type="button" aria-pressed={value === false} onClick={() => pick(false)} className={`${base} ${value === false ? on : off}`}>
                No
            </button>
        </div>
    );
}

export default function PatientQuestionnaire({ sections, isFemale, saved, postUrl, skipUrl, canSkip }: Props) {
    // Staff flow (before a patient is chosen) has no known gender, so show a picker.
    const [gender, setGender] = useState('');
    const needsGenderPicker = Boolean(canSkip) && !isFemale;
    const showWomen = isFemale || gender === 'female';

    const generalKeys = sections.general.map((q) => q.key);
    const womenKeys = sections.women.map((q) => q.key);

    const { data, setData, post, processing, errors, transform } = useForm<QuestionnaireForm>({
        answers: Object.fromEntries(
            [...generalKeys, ...womenKeys].map((key) => [key, saved?.answers?.[key] ?? null]),
        ),
        conditions: saved?.conditions ?? [],
        agreed: false,
    });

    // Blank (null) answers are not sent. Women answers are not sent when the section is hidden.
    transform((d) => ({
        ...d,
        answers: Object.fromEntries(
            Object.entries(d.answers).filter(
                ([key, v]) => v !== null && (showWomen || !womenKeys.includes(key)),
            ),
        ),
    }));

    const setAnswer = (key: string, value: boolean | null) =>
        setData('answers', { ...data.answers, [key]: value });

    const toggleCondition = (key: string) =>
        setData(
            'conditions',
            data.conditions.includes(key) ? data.conditions.filter((k) => k !== key) : [...data.conditions, key],
        );

    // Only the required (general) questions count toward progress.
    const answeredCount = generalKeys.filter((k) => data.answers[k] !== null && data.answers[k] !== undefined).length;

    const fieldErrors = errors as Record<string, string>;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(postUrl ?? '/patient/questionnaire');
    };

    const renderQuestions = (questions: Question[], optional = false) => (
        <ul className="divide-y divide-gray-100">
            {questions.map((q) => {
                const invalid = Boolean(fieldErrors[`answers.${q.key}`]);

                return (
                    <li key={q.key} className="flex items-center justify-between gap-4 py-3">
                        <span className={`text-sm ${invalid ? 'text-red-600' : 'text-gray-800'}`}>{q.label}</span>
                        <YesNo
                            value={data.answers[q.key] ?? null}
                            onChange={(v) => setAnswer(q.key, v)}
                            invalid={invalid}
                            clearable={optional}
                        />
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
                    {/* {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                Please answer every question and confirm the statement at the bottom.
                            </AlertDescription>
                        </Alert>
                    )} */}

                    {needsGenderPicker && (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="max-w-xs space-y-1">
                                    <Label htmlFor="gender" className="font-semibold">
                                        Patient gender
                                    </Label>
                                    <Select value={gender} onValueChange={setGender}>
                                        <SelectTrigger id="gender">
                                            <SelectValue placeholder="Select gender" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="male">Male</SelectItem>
                                            <SelectItem value="female">Female</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                <span>Answer the following questions by Yes or No</span>
                                <span className="text-xs font-normal text-muted-foreground">
                                    {answeredCount} / {generalKeys.length} answered
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>{renderQuestions(sections.general)}</CardContent>
                    </Card>

                    {showWomen && sections.women.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Women
                                    <span className="ml-2 text-xs font-normal text-muted-foreground">
                                        Optional. Click a selected answer again to clear it.
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>{renderQuestions(sections.women, true)}</CardContent>
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