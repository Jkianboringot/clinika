import { useState } from 'react';
import { AlertTriangle, ChevronDown, ChevronUp, ClipboardList } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type Question = { key: string; label: string };

export type QuestionnaireSections = {
    general: Question[];
    women: Question[];
    conditions: Question[];
};

export type QuestionnaireData = {
    answers: Record<string, boolean>;
    conditions: string[];
    updated_at: string | null;
} | null;

interface Props {
    sections: QuestionnaireSections;
    questionnaire: QuestionnaireData;
}

export default function QuestionnaireSummary({ sections, questionnaire }: Props) {
    const [open, setOpen] = useState(false);

    if (!questionnaire) {
        return (
            <Card className="border-amber-300 bg-amber-50/60">
                <CardContent className="flex items-center gap-2 py-3 text-sm text-amber-800">
                    <AlertTriangle className="h-4 w-4" />
                    No medical questionnaire on file for this patient.
                </CardContent>
            </Card>
        );
    }

    const answers = questionnaire.answers;
    const yesAnswers = [...sections.general, ...sections.women].filter((q) => answers[q.key] === true);
    const tickedConditions = sections.conditions.filter((c) => questionnaire.conditions.includes(c.key));
    const flagged = yesAnswers.length + tickedConditions.length;

    const allQuestions = [...sections.general, ...sections.women.filter((q) => q.key in answers)];

    return (
        <Card className={flagged > 0 ? 'border-amber-300 bg-amber-50/60' : ''}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
            >
                <span className="flex items-center gap-2 text-sm font-semibold text-ink">
                    <ClipboardList className="h-4 w-4" />
                    Medical questionnaire
                    {questionnaire.updated_at && (
                        <span className="text-xs font-normal text-muted-foreground">
                            · answered {questionnaire.updated_at}
                        </span>
                    )}
                </span>
                <span className="flex items-center gap-3 text-sm">
                    {flagged > 0 ? (
                        <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                            {flagged} to review
                        </span>
                    ) : (
                        <span className="text-xs text-muted-foreground">Nothing flagged</span>
                    )}
                    {open ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                </span>
            </button>

            {open && (
                <CardContent className="space-y-4 border-t pt-4">
                    {flagged > 0 ? (
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
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            The patient answered No to every question and ticked no conditions.
                        </p>
                    )}

                    <div>
                        <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            All answers
                        </p>
                        <ul className="divide-y divide-gray-100">
                            {allQuestions.map((q) => (
                                <li key={q.key} className="flex items-center justify-between gap-4 py-2 text-sm">
                                    <span className="text-gray-800">{q.label}</span>
                                    <span
                                        className={
                                            answers[q.key]
                                                ? 'font-semibold text-red-700'
                                                : 'text-gray-500'
                                        }
                                    >
                                        {answers[q.key] ? 'Yes' : 'No'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </CardContent>
            )}
        </Card>
    );
}