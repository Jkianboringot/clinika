

import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Question = { key: string; label: string };

interface Props {
    patient: { id: number; full_name: string };
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    questionnaire: {
        answers: Record<string, boolean>;
        conditions: string[];
        updated_at: string | null;
    } | null;
}

function AnswerBadge({ value }: { value: boolean | undefined }) {
    if (value === undefined) {
        return <span className="text-xs text-muted-foreground">—</span>;
    }

    return (
        <span
            className={`inline-flex w-12 justify-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ${
                value ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-gray-50 text-gray-600 ring-gray-200'
            }`}
        >
            {value ? 'Yes' : 'No'}
        </span>
    );
}

export default function PatientQuestionnaireView({ patient, sections, questionnaire }: Props) {
    return (
        <>
            <Head title={`Medical Questionnaire - ${patient.full_name}`} />

            <div className="mx-auto w-full max-w-4xl p-4">
                <Link
                    href="/appointments"
                    className="mb-3 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to appointments
                </Link>

                <div className="mb-4">
                    <h1 className="text-2xl font-bold">Medical Questionnaire</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        {patient.full_name}
                        {questionnaire?.updated_at && ` · last updated ${questionnaire.updated_at}`}
                    </p>
                </div>

                {!questionnaire ? (
                    <Card>
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            This patient has not answered the medical questionnaire yet.
                        </CardContent>
                    </Card>
                ) : (
                    <QuestionnaireDetails sections={sections} questionnaire={questionnaire} />
                )}
            </div>
        </>
    );
}

function QuestionnaireDetails({
    sections,
    questionnaire,
}: {
    sections: Props['sections'];
    questionnaire: NonNullable<Props['questionnaire']>;
}) {
    const { answers, conditions } = questionnaire;

    // The women-only questions only exist in the answers for female patients.
    const womenQuestions = sections.women.filter((q) => q.key in answers);
    const allQuestions = [...sections.general, ...womenQuestions];

    const yesAnswers = allQuestions.filter((q) => answers[q.key] === true);
    const tickedConditions = sections.conditions.filter((c) => conditions.includes(c.key));

    const nothingToFlag = yesAnswers.length === 0 && tickedConditions.length === 0;

    return (
        <div className="space-y-4">
            {/* What the dentist needs to see first */}
            <Card className={nothingToFlag ? '' : 'border-amber-300 bg-amber-50/60'}>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        {!nothingToFlag && <AlertTriangle className="h-4 w-4 text-amber-600" />}
                        Needs attention
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {nothingToFlag ? (
                        <p className="text-sm text-muted-foreground">
                            The patient answered No to every question and ticked no conditions.
                        </p>
                    ) : (
                        <>
                            {yesAnswers.length > 0 && (
                                <div>
                                    <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        Answered Yes
                                    </p>
                                    <ul className="list-inside list-disc space-y-1 text-sm">
                                        {yesAnswers.map((q) => (
                                            <li key={q.key}>{q.label}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {tickedConditions.length > 0 && (
                                <div>
                                    <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        Conditions
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {tickedConditions.map((c) => (
                                            <span
                                                key={c.key}
                                                className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800"
                                            >
                                                {c.label}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Full answers */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">All answers</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul className="divide-y divide-gray-100">
                        {allQuestions.map((q) => (
                            <li key={q.key} className="flex items-center justify-between gap-4 py-2.5">
                                <span className="text-sm text-gray-800">{q.label}</span>
                                <AnswerBadge value={answers[q.key]} />
                            </li>
                        ))}
                    </ul>
                </CardContent>
            </Card>
        </div>
    );
}

PatientQuestionnaireView.layout = {
    breadcrumbs: [
        {
            title: 'Medical Questionnaire',
            href: '/patients',
        },
    ],
};
