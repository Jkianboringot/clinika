import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { DateCalendar, formatIsoDate } from '@/components/ui/date-picker';
import { TimeSlots, formatTime } from '@/components/ui/time-slots';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CircleAlert } from 'lucide-react';
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
    description: string | null;
    appointment_date: string; // "YYYY-MM-DD"
    appointment_time: string; // "HH:mm"
}

interface AppointmentForm {
    patient_id: string;
    procedure_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
}

interface Props {
    appointment: Appointment;
    patients: Option[];
    procedures: Option[];
}

export default function Edit({ appointment, patients, procedures }: Props) {
    const { data, setData, put, processing, errors } = useForm<AppointmentForm>({
        patient_id: String(appointment.patient_id),
        procedure_id: String(appointment.procedure_id),
        description: appointment.description ?? '',
        appointment_date: appointment.appointment_date,
        appointment_time: appointment.appointment_time?.slice(0, 5) ?? '',
    });

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
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">Edit Appointment</h1>
                    <p className="mt-1 text-sm text-subtle">Update this patient visit</p>
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
                                    {patients.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.full_name}
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
                                    {procedures.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.procedure_id && (
                                <p className="text-sm text-danger">{errors.procedure_id}</p>
                            )}
                        </div>
                    </div>

                    {/* ROW 2: schedule (calendar + time side by side) */}
                    <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                        <div className="mb-5 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-ink">Schedule</h2>
                            {scheduleSummary && (
                                <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {scheduleSummary}
                                </span>
                            )}
                        </div>

                        <div className="grid gap-6 md:grid-cols-[300px_1fr]">
                            <div>
                                <p className="mb-3 text-sm font-medium text-ink">Date</p>
                                <DateCalendar
                                    value={data.appointment_date}
                                    onChange={(value) => setData('appointment_date', value)}
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

                                <TimeSlots
                                    value={data.appointment_time}
                                    onChange={(value) => setData('appointment_time', value)}
                                />
                                {errors.appointment_time && (
                                    <p className="mt-2 text-sm text-danger">
                                        {errors.appointment_time}
                                    </p>
                                )}
                            </div>
                        </div>
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