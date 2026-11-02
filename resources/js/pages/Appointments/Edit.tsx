import { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateCalendar, formatIsoDate, type DayStatus } from '@/components/ui/date-picker';
import { TimeSlots, formatTime } from '@/components/ui/time-slots';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ClipboardList, CircleAlert } from 'lucide-react';
import { update } from '@/routes/appointments';

interface Option {
    id: number;
    full_name?: string;
    name?: string;
}

interface Appointment {
    id: number;
    patient_id: number;
    procedure_id: number;
    dentist_id: number | null;
    description: string | null;
    appointment_date: string; // "YYYY-MM-DD"
    appointment_time: string; // "HH:mm:ss" or "HH:mm"
}

type Question = { key: string; label: string };

interface QuestionnaireData {
    answers: Record<string, boolean | null>;
    conditions: string[];
    agreed: boolean;
}

interface AppointmentForm {
    patient_id: string;
    procedure_id: string;
    dentist_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
    questionnaire: QuestionnaireData | null; // null = not edited
}

interface Props {
    appointment: Appointment;
    patients: Option[];
    procedures: Option[];
    dentists: Option[];
    sections: { general: Question[]; women: Question[]; conditions: Question[] };
    isFemale: boolean;
    questionnaire: { answers: Record<string, boolean>; conditions: string[] } | null;
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

export default function Edit({
    appointment,
    patients,
    procedures,
    dentists,
    sections,
    isFemale,
    questionnaire,
}: Props) {
    const initialTime = appointment.appointment_time?.slice(0, 5) ?? '';

    const { data, setData, put, processing, errors } = useForm<AppointmentForm>({
        patient_id: String(appointment.patient_id),
        procedure_id: String(appointment.procedure_id),
        dentist_id: appointment.dentist_id ? String(appointment.dentist_id) : '',
        description: appointment.description ?? '',
        appointment_date: appointment.appointment_date,
        appointment_time: initialTime,
        questionnaire: null,
    });

    // Availability for the chosen dentist. This appointment is excluded from its own
    // count and slot, so the time it already has stays selectable.
    const [month, setMonth] = useState(() => appointment.appointment_date.slice(0, 7));
    const [statuses, setStatuses] = useState<Record<string, DayStatus>>({});
    const [openTimes, setOpenTimes] = useState<string[]>([]);
    const [loadingTimes, setLoadingTimes] = useState(false);

    const patientOptions = patients.map((p) => ({
        value: String(p.id),
        label: p.full_name ?? p.name ?? '',
    }));

    const procedureOptions = procedures.map((p) => ({
        value: String(p.id),
        label: p.name ?? p.full_name ?? '',
    }));

    const dentistOptions = dentists.map((d) => ({
        value: String(d.id),
        label: d.name ?? d.full_name ?? '',
    }));

    // Changing the dentist invalidates the chosen day and time.
    const chooseDentist = (value: string) => {
        setStatuses({});
        setOpenTimes([]);
        setData({ ...data, dentist_id: value, appointment_date: '', appointment_time: '' });
    };

    // Changing the day invalidates the chosen time.
    const chooseDate = (value: string) => {
        setData({ ...data, appointment_date: value, appointment_time: '' });
    };

    // Day statuses for the visible month of the chosen dentist.
    useEffect(() => {
        if (!data.dentist_id) return;

        let active = true;
        const params = new URLSearchParams({
            month,
            dentist_id: data.dentist_id,
            appointment_id: String(appointment.id),
        });

        fetch(`/appointments/day-statuses?${params}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((json) => {
                if (active && json.days) setStatuses(json.days);
            });

        return () => {
            active = false;
        };
    }, [month, data.dentist_id, appointment.id]);

    // Open times for the chosen dentist on the chosen day.
    useEffect(() => {
        if (!data.dentist_id || !data.appointment_date) {
            setOpenTimes([]);
            return;
        }

        let active = true;
        setLoadingTimes(true);

        const params = new URLSearchParams({
            date: data.appointment_date,
            dentist_id: data.dentist_id,
            appointment_id: String(appointment.id),
        });

        fetch(`/appointments/open-slots?${params}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((json) => {
                if (active) setOpenTimes(json.times ?? []);
            })
            .finally(() => {
                if (active) setLoadingTimes(false);
            });

        return () => {
            active = false;
        };
    }, [data.appointment_date, data.dentist_id, appointment.id]);

    // Block past dates. Local time, so it matches the calendar.
    const todayIso = (() => {
        const t = new Date();
        return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(
            t.getDate(),
        ).padStart(2, '0')}`;
    })();

    // Questionnaire
    const questionKeys = [
        ...sections.general.map((q) => q.key),
        ...(isFemale ? sections.women.map((q) => q.key) : []),
    ];

    const openQuestionnaire = () =>
        setData('questionnaire', {
            answers: Object.fromEntries(
                questionKeys.map((k) => [k, questionnaire?.answers?.[k] ?? null]),
            ),
            conditions: questionnaire?.conditions ?? [],
            agreed: false,
        });

    const closeQuestionnaire = () => setData('questionnaire', null);

    const setAnswer = (key: string, value: boolean) => {
        if (!data.questionnaire) return;
        setData('questionnaire', {
            ...data.questionnaire,
            answers: { ...data.questionnaire.answers, [key]: value },
        });
    };

    const toggleCondition = (key: string) => {
        if (!data.questionnaire) return;
        const current = data.questionnaire.conditions;
        setData('questionnaire', {
            ...data.questionnaire,
            conditions: current.includes(key)
                ? current.filter((k) => k !== key)
                : [...current, key],
        });
    };

    const renderQuestions = (questions: Question[]) => (
        <ul className="divide-y divide-gray-100">
            {questions.map((q) => (
                <li key={q.key} className="flex items-center justify-between gap-4 py-3">
                    <span className="text-sm text-gray-800">{q.label}</span>
                    <YesNo
                        value={data.questionnaire?.answers[q.key] ?? null}
                        onChange={(v) => setAnswer(q.key, v)}
                    />
                </li>
            ))}
        </ul>
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(appointment.id).url);
    };

    const scheduleSummary =
        data.appointment_date && data.appointment_time
            ? `${formatIsoDate(data.appointment_date)} · ${formatTime(data.appointment_time)}`
            : null;

    return (
        <>
            <Head title="Edit Appointment" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-ink">Edit Appointment</h1>
                        <p className="mt-1 text-sm text-subtle">Update this patient visit</p>
                    </div>
                    <button
                        type="button"
                        onClick={data.questionnaire ? closeQuestionnaire : openQuestionnaire}
                        className="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <ClipboardList className="h-3.5 w-3.5" />
                        {data.questionnaire ? 'Cancel questionnaire edit' : 'Edit questionnaire'}
                    </button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm text-danger">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* ROW 1: who and what */}
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="patient_id" className="text-sm font-medium text-ink">
                                Patient
                            </Label>
                            <Select
                                value={data.patient_id}
                                onValueChange={(value) => setData('patient_id', value)}
                            >
                                <SelectTrigger id="patient_id" className="h-10 w-full border-gray-200">
                                    <SelectValue placeholder="Select a patient" />
                                </SelectTrigger>
                                <SelectContent>
                                    {patientOptions.map((p) => (
                                        <SelectItem key={p.value} value={p.value}>
                                            {p.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.patient_id && (
                                <p className="text-sm text-danger">{errors.patient_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="procedure_id" className="text-sm font-medium text-ink">
                                Procedure
                            </Label>
                            <Select
                                value={data.procedure_id}
                                onValueChange={(value) => setData('procedure_id', value)}
                            >
                                <SelectTrigger id="procedure_id" className="h-10 w-full border-gray-200">
                                    <SelectValue placeholder="Select a procedure" />
                                </SelectTrigger>
                                <SelectContent>
                                    {procedureOptions.map((p) => (
                                        <SelectItem key={p.value} value={p.value}>
                                            {p.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.procedure_id && (
                                <p className="text-sm text-danger">{errors.procedure_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="dentist_id" className="text-sm font-medium text-ink">
                                Dentist
                            </Label>
                            <Select value={data.dentist_id} onValueChange={chooseDentist}>
                                <SelectTrigger id="dentist_id" className="h-10 w-full border-gray-200">
                                    <SelectValue placeholder="Select a dentist" />
                                </SelectTrigger>
                                <SelectContent>
                                    {dentistOptions.map((d) => (
                                        <SelectItem key={d.value} value={d.value}>
                                            {d.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.dentist_id && (
                                <p className="text-sm text-danger">{errors.dentist_id}</p>
                            )}
                        </div>
                    </div>

                    {/* ROW 2: schedule, driven by the chosen dentist */}
                    <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                        <div className="mb-5 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-ink">Schedule</h2>
                            {scheduleSummary && (
                                <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {scheduleSummary}
                                </span>
                            )}
                        </div>

                        {!data.dentist_id ? (
                            <p className="text-sm text-subtle">
                                Select a dentist to see their open days and times.
                            </p>
                        ) : (
                            <div className="grid gap-6 md:grid-cols-[300px_1fr]">
                                <div>
                                    <p className="mb-3 text-sm font-medium text-ink">Date</p>
                                    <DateCalendar
                                        value={data.appointment_date}
                                        onChange={chooseDate}
                                        minDate={todayIso}
                                        statuses={statuses}
                                        onMonthChange={setMonth}
                                    />
                                    {errors.appointment_date && (
                                        <p className="mt-2 text-sm text-danger">
                                            {errors.appointment_date}
                                        </p>
                                    )}
                                </div>

                                <div className="md:border-l md:border-gray-200 md:pl-6">
                                    <div className="mb-3 flex items-center justify-between">
                                        <p className="text-sm font-medium text-ink">Time</p>
                                        {data.appointment_time && (
                                            <span className="text-xs font-medium text-blue-600">
                                                {formatTime(data.appointment_time)}
                                            </span>
                                        )}
                                    </div>

                                    {!data.appointment_date ? (
                                        <p className="text-sm text-subtle">Select a date first.</p>
                                    ) : loadingTimes ? (
                                        <p className="text-sm text-subtle">Loading...</p>
                                    ) : (
                                        <TimeSlots
                                            value={data.appointment_time}
                                            onChange={(value) => setData('appointment_time', value)}
                                            available={openTimes}
                                            emptyText="No open times on this day."
                                        />
                                    )}

                                    {errors.appointment_time && (
                                        <p className="mt-2 text-sm text-danger">
                                            {errors.appointment_time}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* ROW 3: notes, full width */}
                    <div className="space-y-1.5">
                        <Label htmlFor="description" className="text-sm font-medium text-ink">
                            Notes
                        </Label>
                        <Textarea
                            id="description"
                            placeholder="Any additional notes for this visit"
                            value={data.description}
                            maxLength={1000}
                            onChange={(e) => setData('description', e.target.value)}
                            className="min-h-[100px] resize-none border-gray-200"
                        />
                        {errors.description && (
                            <p className="text-sm text-danger">{errors.description}</p>
                        )}
                    </div>

                    {/* ROW 4: medical questionnaire (only when "Edit questionnaire" is clicked) */}
                    {data.questionnaire && (
                        <div className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Medical Questionnaire (Yes / No)
                                    </CardTitle>
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
                                        Conditions the patient has had or has now
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
                                                    checked={data.questionnaire!.conditions.includes(c.key)}
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
                                    checked={data.questionnaire.agreed}
                                    onChange={(e) =>
                                        setData('questionnaire', {
                                            ...data.questionnaire!,
                                            agreed: e.target.checked,
                                        })
                                    }
                                    className="mt-0.5 h-4 w-4 rounded border-gray-300"
                                />
                                <span>
                                    To the best of my knowledge, all of the preceding answers are true
                                    and correct.
                                </span>
                            </label>
                        </div>
                    )}

                    <div className="flex justify-end border-t border-gray-200 pt-5">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-10 bg-blue-600 px-6 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            Update Appointment
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Edit Appointment',
            href: '/appointments/edit',
        },
    ],
};