import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { DateCalendar, formatIsoDate } from '@/components/ui/date-picker';
import { TimeSlots, formatTime } from '@/components/ui/time-slots';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Check, ChevronsUpDown, CircleAlert } from 'lucide-react';
import { cn } from '@/lib/utils';
import { store } from '@/routes/appointments';

interface Option {
    id: number;
    full_name?: string;
    name?: string;
}

interface AppointmentForm {
    patient_id: string;
    procedure_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
}

interface Props {
    patients: Option[];
    procedures: Option[];
}

interface ComboboxProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
}

function Combobox({
    id,
    value,
    onChange,
    options,
    placeholder,
    searchPlaceholder,
    emptyText,
}: ComboboxProps) {
    const [open, setOpen] = useState(false);
    const selected = options.find((o) => o.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="h-10 w-full justify-between border-gray-200 font-normal"
                >
                    <span className={cn('truncate', !selected && 'text-muted-foreground')}>
                        {selected ? selected.label : placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="p-0"
                align="start"
                style={{ width: 'var(--radix-popover-trigger-width)' }}
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyText}</CommandEmpty>
                        <CommandGroup>
                            {options.map((o) => (
                                <CommandItem
                                    key={o.value}
                                    // id is included so duplicate names stay unique
                                    value={`${o.label} ${o.value}`}
                                    onSelect={() => {
                                        onChange(o.value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            value === o.value ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    {o.label}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

export default function Create({ patients, procedures }: Props) {
    const { data, setData, post, processing, errors } = useForm<AppointmentForm>({
        patient_id: '',
        procedure_id: '',
        description: '',
        appointment_date: '',
        appointment_time: '',
    });

    const patientOptions = patients.map((p) => ({
        value: String(p.id),
        label: p.full_name ?? p.name ?? '',
    }));

    const procedureOptions = procedures.map((p) => ({
        value: String(p.id),
        label: p.name ?? p.full_name ?? '',
    }));

    // Block past dates when booking. Local time, so it matches the calendar.
    const todayIso = (() => {
        const t = new Date();
        return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(
            t.getDate(),
        ).padStart(2, '0')}`;
    })();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    const scheduleSummary =
        data.appointment_date && data.appointment_time
            ? `${formatIsoDate(data.appointment_date)} · ${formatTime(data.appointment_time)}`
            : null;

    return (
        <>
            <Head title="New Appointment" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">New Appointment</h1>
                    <p className="mt-1 text-sm text-subtle">Schedule a patient visit</p>
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
                            <Combobox
                                id="patient_id"
                                value={data.patient_id}
                                onChange={(value) => setData('patient_id', value)}
                                options={patientOptions}
                                placeholder="Select a patient"
                                searchPlaceholder="Search patient..."
                                emptyText="No patient found."
                            />
                            {errors.patient_id && (
                                <p className="text-sm text-danger">{errors.patient_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="procedure_id" className="text-sm font-medium text-ink">
                                Procedure
                            </Label>
                            <Combobox
                                id="procedure_id"
                                value={data.procedure_id}
                                onChange={(value) => setData('procedure_id', value)}
                                options={procedureOptions}
                                placeholder="Select a procedure"
                                searchPlaceholder="Search procedure..."
                                emptyText="No procedure found."
                            />
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
                                    minDate={todayIso}
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
                            Save Appointment
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'New Appointment',
            href: '/appointments/create',
        },
    ],
};