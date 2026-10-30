import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Procedure = { id: number; name: string };

type AppointmentForm = {
    procedure_id: string;
    appointment_date: string;
    appointment_time: string;
    description: string;
};

const today = () => new Date().toISOString().split('T')[0];

export default function PatientAppointmentCreate({ procedures }: { procedures: Procedure[] }) {
    const { data, setData, post, processing, errors } = useForm<AppointmentForm>({
        procedure_id: '',
        appointment_date: '',
        appointment_time: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/patient/appointments');
    };

    return (
        <>
            <Head title="Book Appointment" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold">Book an appointment</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Choose a procedure and your preferred date and time.
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4 pt-6">
                            <div className="space-y-1">
                                <Label htmlFor="procedure_id">Procedure</Label>
                                <Select
                                    value={data.procedure_id}
                                    onValueChange={(value) => setData('procedure_id', value)}
                                >
                                    <SelectTrigger id="procedure_id">
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
                                    <p className="text-sm text-red-600">{errors.procedure_id}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <Label htmlFor="appointment_date">Date</Label>
                                    <Input
                                        id="appointment_date"
                                        type="date"
                                        min={today()}
                                        value={data.appointment_date}
                                        onChange={(e) => setData('appointment_date', e.target.value)}
                                    />
                                    {errors.appointment_date && (
                                        <p className="text-sm text-red-600">{errors.appointment_date}</p>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="appointment_time">Time</Label>
                                    <Input
                                        id="appointment_time"
                                        type="time"
                                        value={data.appointment_time}
                                        onChange={(e) => setData('appointment_time', e.target.value)}
                                    />
                                    {errors.appointment_time && (
                                        <p className="text-sm text-red-600">{errors.appointment_time}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="description">Notes (optional)</Label>
                                <textarea
                                    id="description"
                                    rows={4}
                                    maxLength={1000}
                                    placeholder="Anything the dentist should know?"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                {errors.description && (
                                    <p className="text-sm text-red-600">{errors.description}</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 border-t pt-4">
                                <Button asChild variant="outline">
                                    <Link href="/patient/appointments">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Book appointment
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}