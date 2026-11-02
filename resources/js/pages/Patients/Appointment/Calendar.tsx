import { useEffect, useState } from 'react';
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

interface AppointmentForm {
    procedure_id: string;
    dentist_id: string;
    description: string;
    appointment_date: string;
    appointment_time: string;
}

interface Props {
    dentists: Option[];
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

const currentMonth = () => {
    const t = new Date();
    return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}`;
};

export default function Calendar({ dentists, procedures }: Props) {
    const { data, setData, post, processing, errors } = useForm<AppointmentForm>({
        procedure_id: '',
        dentist_id: '',
        description: '',
        appointment_date: '',
        appointment_time: '',
    });

    // Availability for the chosen dentist
    const [month, setMonth] = useState(currentMonth);
    const [statuses, setStatuses] = useState<Record<string, DayStatus>>({});
    const [openTimes, setOpenTimes] = useState<string[]>([]);
    const [loadingTimes, setLoadingTimes] = useState(false);

    const procedureOptions = procedures.map((p) => ({
        value: String(p.id),
        label: p.name,
    }));

    const dentistOptions = dentists.map((d) => ({
        value: String(d.id),
        label: d.name,
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

    // Day statuses for the visible month of the chosen dentist.
    useEffect(() => {
        if (!data.dentist_id) return;

        let active = true;
        const params = new URLSearchParams({ month, dentist_id: data.dentist_id });

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
        });

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
        post('/patient/appointments');
    };

    const scheduleSummary =
        data.appointment_date && data.appointment_time
            ? `${formatIsoDate(data.appointment_date)} · ${formatTime(data.appointment_time)}`
            : null;

    return (
        <>
            <Head title="Book an Appointment" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-ink">Book an Appointment</h1>
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

                    {/* ROW 1: what and who */}
                    <div className="grid gap-5 md:grid-cols-2">
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
                            disabled={processing || !data.procedure_id || !data.dentist_id || !data.appointment_time}
                            className="h-10 bg-blue-600 px-6 font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            Submit Appointment Request
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Calendar.layout = {
    breadcrumbs: [
        { title: 'My Appointments', href: '/patient/appointments' },
        { title: 'Book', href: '/patient/appointments/create' },
    ],
};