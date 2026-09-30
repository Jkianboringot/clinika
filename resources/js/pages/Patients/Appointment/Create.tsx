import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Option = { id: number; name: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Appointments', href: '/patient/appointments' },
    { title: 'Book', href: '/patient/appointments/create' },
];

// dentists / procedures are optional: pass them from PatientAppointmentController@create
// and make sure the field names below match your validation rules.
export default function PatientAppointmentsCreate({ dentists = [], procedures = [] }: { dentists?: Option[]; procedures?: Option[] }) {
    const { data, setData, post, processing, errors } = useForm({
        dentist_id: '',
        procedure_id: '',
        appointment_date: '',
        appointment_time: '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/patient/appointments');
    };

    const selectClass =
        'border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Book Appointment" />

            <div className="p-4">
                <Card className="mx-auto max-w-xl">
                    <CardHeader>
                        <CardTitle>Book an Appointment</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            {procedures.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="procedure_id">Procedure</Label>
                                    <select id="procedure_id" className={selectClass} value={data.procedure_id} onChange={(e) => setData('procedure_id', e.target.value)}>
                                        <option value="">Select procedure</option>
                                        {procedures.map((p) => (
                                            <option key={p.id} value={p.id}>{p.name}</option>
                                        ))}
                                    </select>
                                    {errors.procedure_id && <p className="text-sm text-red-600">{errors.procedure_id}</p>}
                                </div>
                            )}

                            {dentists.length > 0 && (
                                <div className="grid gap-2">
                                    <Label htmlFor="dentist_id">Dentist</Label>
                                    <select id="dentist_id" className={selectClass} value={data.dentist_id} onChange={(e) => setData('dentist_id', e.target.value)}>
                                        <option value="">Select dentist</option>
                                        {dentists.map((d) => (
                                            <option key={d.id} value={d.id}>{d.name}</option>
                                        ))}
                                    </select>
                                    {errors.dentist_id && <p className="text-sm text-red-600">{errors.dentist_id}</p>}
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="appointment_date">Date</Label>
                                    <Input id="appointment_date" type="date" value={data.appointment_date} onChange={(e) => setData('appointment_date', e.target.value)} />
                                    {errors.appointment_date && <p className="text-sm text-red-600">{errors.appointment_date}</p>}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="appointment_time">Time</Label>
                                    <Input id="appointment_time" type="time" value={data.appointment_time} onChange={(e) => setData('appointment_time', e.target.value)} />
                                    {errors.appointment_time && <p className="text-sm text-red-600">{errors.appointment_time}</p>}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <textarea id="notes" rows={3} className={selectClass + ' h-auto py-2'} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                                {errors.notes && <p className="text-sm text-red-600">{errors.notes}</p>}
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/patient/appointments">Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Booking...' : 'Book Appointment'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}