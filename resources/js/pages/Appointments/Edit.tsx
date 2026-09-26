import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
        appointment_time: appointment.appointment_time,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(appointment.id).url);
    };

    return (
        <>
            <Head title="Edit Appointment" />

            <div className="mx-auto w-full max-w-3xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Appointment</h1>
                    <p className="mt-1 text-sm text-subtle">Update this patient visit</p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-gray-200 bg-white p-5"
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

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="patient_id" className="font-semibold text-ink">
                                Patient
                            </Label>
                            <Select
                                value={data.patient_id}
                                onValueChange={(value) => setData('patient_id', value)}
                            >
                                <SelectTrigger id="patient_id" className="border-gray-200">
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
                                <p className="mt-1.5 text-sm text-danger">{errors.patient_id}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="procedure_id" className="font-semibold text-ink">
                                Procedure
                            </Label>
                            <Select
                                value={data.procedure_id}
                                onValueChange={(value) => setData('procedure_id', value)}
                            >
                                <SelectTrigger id="procedure_id" className="border-gray-200">
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
                                <p className="mt-1.5 text-sm text-danger">{errors.procedure_id}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="appointment_date" className="font-semibold text-ink">
                                Date
                            </Label>
                            <Input
                                id="appointment_date"
                                type="date"
                                value={data.appointment_date}
                                onChange={(e) => setData('appointment_date', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.appointment_date && (
                                <p className="mt-1.5 text-sm text-danger">{errors.appointment_date}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="appointment_time" className="font-semibold text-ink">
                                Time
                            </Label>
                            <Input
                                id="appointment_time"
                                type="time"
                                value={data.appointment_time}
                                onChange={(e) => setData('appointment_time', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.appointment_time && (
                                <p className="mt-1.5 text-sm text-danger">{errors.appointment_time}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="description" className="font-semibold text-ink">
                            Notes
                        </Label>
                        <Textarea
                            id="description"
                            placeholder="Any additional notes for this visit"
                            value={data.description}
                            maxLength={1000}
                            onChange={(e) => setData('description', e.target.value)}
                            className="border-gray-200"
                        />
                        {errors.description && (
                            <p className="mt-1.5 text-sm text-danger">{errors.description}</p>
                        )}
                    </div>

                    <div className="flex justify-end border-t border-gray-200 pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 font-bold text-white hover:bg-blue-700 disabled:opacity-60"
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