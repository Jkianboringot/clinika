import { Fragment, useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { DateCalendar, formatIsoDate, type DayStatus } from '@/components/ui/date-picker';
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

interface Option {
    id: number;
    name: string;
}

type ScheduleSlot = { day: string; start: string; end: string };

interface DentistDetail {
    specialization: string[] | string | null;
    available_schedule: ScheduleSlot[] | string | null;
}

interface DentistOption extends Option {
    dentist_detail?: DentistDetail | null;
}

interface EditingAppointment {
    id: number;
    procedure_id: string;
    dentist_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
}

interface AppointmentForm {
    procedure_id: string;
    dentist_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
}

interface Props {
    dentists: DentistOption[];
    procedures: Option[];
    appointment?: EditingAppointment | null;
}

interface ComboboxDetail {
    label: string;
    value: string;
}

interface ComboboxOption {
    value: string;
    label: string;
    details?: ComboboxDetail[];
}

interface ComboboxProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: ComboboxOption[];
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
}

const listText = (value: string[] | string | null | undefined) =>
    Array.isArray(value) ? value.join(', ') : (value ?? '');

const capitalize = (text: string) => `${text.charAt(0).toUpperCase()}${text.slice(1)}`;

// One row for specialization, then one row per scheduled day.
const dentistDetails = (detail: DentistDetail | null | undefined): ComboboxDetail[] => {
    if (!detail) return [];

    const rows: ComboboxDetail[] = [];

    const specialization = listText(detail.specialization);
    if (specialization) {
        rows.push({ label: 'Specialization', value: specialization });
    }

    if (Array.isArray(detail.available_schedule)) {
        detail.available_schedule.forEach((slot) => {
            rows.push({
                label: capitalize(slot.day),
                value: `${slot.start} – ${slot.end}`,
            });
        });
    } else if (detail.available_schedule) {
        rows.push({ label: 'Schedule', value: detail.available_schedule });
    }

    return rows;
};

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
                                    // details are included so search also matches specialization or schedule
                                    value={`${o.label} ${
                                        o.details?.map((d) => `${d.label} ${d.value}`).join(' ') ?? ''
                                    } ${o.value}`}
                                    onSelect={() => {
                                        onChange(o.value);
                                        setOpen(false);
                                    }}
                                    className="items-start py-2.5"
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 mt-0.5 h-4 w-4 shrink-0',
                                            value === o.value ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                                        <span className="font-semibold text-ink">{o.label}</span>

                                        {o.details && o.details.length > 0 && (
                                            <div className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-0.5 rounded-md border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                                                {o.details.map((d, i) => (
                                                    <Fragment key={`${d.label}-${i}`}>
                                                        <span className="text-muted-foreground">
                                                            {d.label}
                                                        </span>
                                                        <span className="font-medium text-gray-800">
                                                            {d.value}
                                                        </span>
                                                    </Fragment>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

const monthOf = (iso: string) => iso.slice(0, 7);

const currentMonth = () => {
    const t = new Date();
    return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}`;
};

export default function Create({ dentists, procedures, appointment }: Props) {
    const editing = !!appointment;

    const { data, setData, post, put, processing, errors } = useForm<AppointmentForm>({
        procedure_id: appointment?.procedure_id ?? '',
        dentist_id: appointment?.dentist_id ?? '',
        description: appointment?.description ?? '',
        appointment_date: appointment?.appointment_date ?? '',
        appointment_time: appointment?.appointment_time ?? '',
    });

    const [month, setMonth] = useState(() =>
        appointment ? monthOf(appointment.appointment_date) : currentMonth(),
    );
    const [statuses, setStatuses] = useState<Record<string, DayStatus>>({});
    const [openTimes, setOpenTimes] = useState<string[]>([]);
    const [loadingTimes, setLoadingTimes] = useState(false);

    const procedureOptions = procedures.map((p) => ({ value: String(p.id), label: p.name }));

    const dentistOptions = dentists.map((d) => ({
        value: String(d.id),
        label: d.name,
        details: dentistDetails(d.dentist_detail),
    }));

    // Block past dates. Local time, so it matches the calendar.
    const todayIso = (() => {
        const t = new Date();
        return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(
            t.getDate(),
        ).padStart(2, '0')}`;
    })();

    // Changing the dentist invalidates the chosen day and time.
    const chooseDentist = (value: string) => {
        setStatuses({});
        setData({ ...data, dentist_id: value, appointment_date: '', appointment_time: '' });
    };

    // Changing the day invalidates the chosen time.
    const chooseDate = (value: string) => {
        setData({ ...data, appointment_date: value, appointment_time: '' });
    };

    // Day statuses for the visible month. Own booking is excluded when rescheduling.
    useEffect(() => {
        if (!data.dentist_id) return;

        let active = true;
        const params = new URLSearchParams({ month, dentist_id: data.dentist_id });
        if (appointment) params.set('appointment_id', String(appointment.id));

        fetch(`/patient/appointments/day-statuses?${params}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((json) => {
                if (active && json.days) setStatuses(json.days);
            });

        return () => {
            active = false;
        };
    }, [month, data.dentist_id]);

    // Open times for the chosen day. Own current time stays open when rescheduling.
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
        });
        if (appointment) params.set('appointment_id', String(appointment.id));

        fetch(`/patient/appointments/open-slots?${params}`, {
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
    }, [data.appointment_date, data.dentist_id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (appointment) {
            put(`/patient/appointments/${appointment.id}`);
        } else {
            post('/patient/appointments');
        }
    };

    const scheduleSummary =
        data.appointment_date && data.appointment_time
            ? `${formatIsoDate(data.appointment_date)} · ${formatTime(data.appointment_time)}`
            : null;

    return (
        <>
            <Head title={editing ? 'Reschedule Appointment' : 'Book an Appointment'} />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">
                        {editing ? 'Reschedule Appointment' : 'Book an Appointment'}
                    </h1>
                    <p className="mt-1 text-sm text-subtle">
                        Pick a dentist, then a day and time that are open
                    </p>
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

                    {/* ROW 1: dentist, full width so the details have room */}
                    <div className="space-y-1.5">
                        <Label htmlFor="dentist_id" className="text-sm font-medium text-ink">
                            Dentist
                        </Label>
                        <Combobox
                            id="dentist_id"
                            value={data.dentist_id}
                            onChange={chooseDentist}
                            options={dentistOptions}
                            placeholder="Select a dentist"
                            searchPlaceholder="Search dentist..."
                            emptyText="No dentist found."
                        />
                        {errors.dentist_id && (
                            <p className="text-sm text-danger">{errors.dentist_id}</p>
                        )}
                    </div>

                    {/* ROW 2: reason for visit */}
                    <div className="space-y-1.5">
                        <Label htmlFor="procedure_id" className="text-sm font-medium text-ink">
                            Reason for visit
                        </Label>
                        <Combobox
                            id="procedure_id"
                            value={data.procedure_id}
                            onChange={(value) => setData('procedure_id', value)}
                            options={procedureOptions}
                            placeholder="Select a reason"
                            searchPlaceholder="Search reason..."
                            emptyText="No reason found."
                        />
                        {errors.procedure_id && (
                            <p className="text-sm text-danger">{errors.procedure_id}</p>
                        )}
                    </div>

                    {/* ROW 3: schedule */}
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

                    {/* ROW 4: notes */}
                    <div className="space-y-1.5">
                        <Label htmlFor="description" className="text-sm font-medium text-ink">
                            Notes (optional)
                        </Label>
                        <Textarea
                            id="description"
                            placeholder="Anything the clinic should know"
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
                            disabled={
                                processing ||
                                !data.procedure_id ||
                                !data.dentist_id ||
                                !data.appointment_time
                            }
                            className="h-10 bg-blue-600 px-6 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {editing ? 'Save New Time' : 'Submit Appointment Request'}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'My Appointments', href: '/patient/appointments' },
        { title: 'Book', href: '/patient/appointments/create' },
    ],
};