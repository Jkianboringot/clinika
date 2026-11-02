// resources/js/pages/Patients/Appointment/Calendar.tsx
import { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatTime } from '@/components/ui/time-slots';
import { cn } from '@/lib/utils';

type DayStatus = 'available' | 'full' | 'unavailable' | 'past';
type MyMark = 'pending' | 'confirmed' | 'reschedule';

interface Props {
    dentists: { id: number; name: string }[];
    procedures: { id: number; name: string }[];
}

const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const pad = (n: number) => String(n).padStart(2, '0');
const toIso = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const monthKey = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;

const DAY_STYLE: Record<DayStatus, string> = {
    available: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100',
    full: 'cursor-not-allowed border-red-200 bg-red-50 text-red-600',
    unavailable: 'cursor-not-allowed border-red-200 bg-red-50 text-red-600',
    past: 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400',
};

const MY_STYLE: Record<MyMark, string> = {
    pending: 'border-yellow-300 bg-yellow-100 text-yellow-800',
    confirmed: 'border-green-300 bg-green-100 text-green-800',
    reschedule: 'border-purple-300 bg-purple-100 text-purple-800',
};

const LEGEND: [string, string][] = [
    ['bg-blue-50 border-blue-200', 'Available'],
    ['bg-yellow-100 border-yellow-300', 'Your pending request'],
    ['bg-purple-100 border-purple-300', 'Reschedule requested'],
    ['bg-green-100 border-green-300', 'Confirmed'],
    ['bg-red-50 border-red-200', 'Fully booked / unavailable'],
    ['bg-gray-100 border-gray-200', 'Past'],
];

const selectClass = 'h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-ink';

export default function Calendar({ dentists, procedures }: Props) {
    const now = new Date();
    const [view, setView] = useState(new Date(now.getFullYear(), now.getMonth(), 1));
    const [days, setDays] = useState<Record<string, DayStatus>>({});
    const [mine, setMine] = useState<Record<string, MyMark>>({});
    const [times, setTimes] = useState<string[]>([]);
    const [loadingTimes, setLoadingTimes] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        procedure_id: '',
        dentist_id: '',
        appointment_date: '',
        appointment_time: '',
        description: '',
    });

    // Calendar colours for the visible month. Refetched when the month or dentist changes.
    useEffect(() => {
        let active = true;
        const params = new URLSearchParams({ month: monthKey(view) });
        if (data.dentist_id) params.set('dentist_id', data.dentist_id);

        fetch(`/patient/appointments/calendar-data?${params}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((json) => {
                if (!active) return;
                setDays(json.days);
                setMine(json.mine);
            });

        return () => {
            active = false;
        };
    }, [view, data.dentist_id]);

    // Open times for the chosen day only.
    useEffect(() => {
        if (!data.appointment_date) {
            setTimes([]);
            return;
        }

        let active = true;
        setLoadingTimes(true);

        const params = new URLSearchParams({ date: data.appointment_date });
        if (data.dentist_id) params.set('dentist_id', data.dentist_id);

        fetch(`/patient/appointments/slots?${params}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((json) => {
                if (active) setTimes(json.times);
            })
            .finally(() => {
                if (active) setLoadingTimes(false);
            });

        return () => {
            active = false;
        };
    }, [data.appointment_date, data.dentist_id]);

    const shiftMonth = (n: number) =>
        setView(new Date(view.getFullYear(), view.getMonth() + n, 1));

    const pickDate = (iso: string) => {
        setData('appointment_date', iso);
        setData('appointment_time', '');
    };

    const changeDentist = (value: string) => {
        setData('dentist_id', value);
        setData('appointment_time', '');
    };

    // Six weeks starting from the Sunday on or before the 1st.
    const first = new Date(view.getFullYear(), view.getMonth(), 1);
    const gridStart = new Date(view.getFullYear(), view.getMonth(), 1 - first.getDay());
    const cells = Array.from(
        { length: 42 },
        (_, i) => new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i),
    );

    const selectedLabel = data.appointment_date
        ? new Date(`${data.appointment_date}T00:00`).toLocaleDateString('en-US', {
              weekday: 'short',
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : '';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/patient/appointments');
    };

    return (
        <>
            <Head title="Book an Appointment" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-ink">Book an Appointment</h1>
                    <p className="mt-1 text-sm text-subtle">Pick a day, then a time that is open</p>
                </div>

                {Object.keys(errors).length > 0 && (
                    <Alert variant="destructive">
                        <CircleAlert />
                        <AlertTitle>Something's not right</AlertTitle>
                        <AlertDescription>
                            <ul className="list-inside list-disc text-sm">
                                {Object.values(errors).map((message, i) => (
                                    <li key={i}>{message}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form
                    onSubmit={submit}
                    className="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="procedure_id" className="text-sm font-medium text-ink">
                                Reason for visit
                            </Label>
                            <select
                                id="procedure_id"
                                value={data.procedure_id}
                                onChange={(e) => setData('procedure_id', e.target.value)}
                                className={selectClass}
                            >
                                <option value="">Select a reason</option>
                                {procedures.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            {errors.procedure_id && (
                                <p className="text-sm text-danger">{errors.procedure_id}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="dentist_id" className="text-sm font-medium text-ink">
                                Preferred dentist
                            </Label>
                            <select
                                id="dentist_id"
                                value={data.dentist_id}
                                onChange={(e) => changeDentist(e.target.value)}
                                className={selectClass}
                            >
                                <option value="">Any available dentist</option>
                                {dentists.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="grid gap-6 md:grid-cols-[minmax(0,1fr)_220px]">
                        {/* Calendar */}
                        <div>
                            <div className="mb-3 flex items-center justify-between">
                                <button
                                    type="button"
                                    onClick={() => shiftMonth(-1)}
                                    aria-label="Previous month"
                                    className="flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-50"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </button>

                                <span className="text-sm font-semibold text-ink">
                                    {view.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}
                                </span>

                                <button
                                    type="button"
                                    onClick={() => shiftMonth(1)}
                                    aria-label="Next month"
                                    className="flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-50"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>

                            <div className="grid grid-cols-7 gap-1 text-center">
                                {WEEKDAYS.map((w) => (
                                    <span key={w} className="pb-1 text-xs font-medium text-subtle">
                                        {w}
                                    </span>
                                ))}

                                {cells.map((d) => {
                                    const key = toIso(d);
                                    const inMonth = d.getMonth() === view.getMonth();
                                    const status = days[key] ?? 'unavailable';
                                    const mark = mine[key];
                                    const selectable = inMonth && status === 'available';
                                    const isSelected = key === data.appointment_date;

                                    return (
                                        <button
                                            key={key}
                                            type="button"
                                            disabled={!selectable}
                                            onClick={() => pickDate(key)}
                                            className={cn(
                                                'h-10 rounded-lg border text-sm font-medium transition',
                                                !inMonth && 'invisible',
                                                inMonth && (mark ? MY_STYLE[mark] : DAY_STYLE[status]),
                                                isSelected && 'ring-2 ring-blue-600 ring-offset-1',
                                            )}
                                        >
                                            {d.getDate()}
                                        </button>
                                    );
                                })}
                            </div>

                            <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-subtle">
                                {LEGEND.map(([cls, label]) => (
                                    <li key={label} className="flex items-center gap-1.5">
                                        <span className={cn('h-3 w-3 rounded border', cls)} />
                                        {label}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Open times for the selected day */}
                        <div className="md:border-l md:border-gray-200 md:pl-6">
                            <div className="mb-3 flex items-center justify-between">
                                <p className="text-sm font-medium text-ink">Open times</p>
                                {data.appointment_time && (
                                    <span className="text-xs font-medium text-blue-600">
                                        {formatTime(data.appointment_time)}
                                    </span>
                                )}
                            </div>

                            {!data.appointment_date ? (
                                <p className="text-sm text-subtle">Select a day to see open times.</p>
                            ) : loadingTimes ? (
                                <p className="text-sm text-subtle">Loading...</p>
                            ) : times.length === 0 ? (
                                <p className="text-sm text-subtle">No open times on this day.</p>
                            ) : (
                                <div className="grid grid-cols-2 gap-2">
                                    {times.map((t) => (
                                        <button
                                            key={t}
                                            type="button"
                                            onClick={() => setData('appointment_time', t)}
                                            className={cn(
                                                'h-9 rounded-lg border text-xs font-medium whitespace-nowrap transition-colors',
                                                data.appointment_time === t
                                                    ? 'border-blue-600 bg-blue-600 font-semibold text-white hover:bg-blue-700'
                                                    : 'border-gray-200 bg-white text-ink hover:border-blue-400 hover:bg-blue-50',
                                            )}
                                        >
                                            {formatTime(t)}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {errors.appointment_time && (
                                <p className="mt-2 text-sm text-danger">{errors.appointment_time}</p>
                            )}
                        </div>
                    </div>

                    {selectedLabel && (
                        <p className="text-sm text-ink">
                            Selected: <span className="font-medium">{selectedLabel}</span>
                            {data.appointment_time && ` at ${formatTime(data.appointment_time)}`}
                        </p>
                    )}

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
                            disabled={processing || !data.procedure_id || !data.appointment_date || !data.appointment_time}
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
    breadcrumbs: [{ title: 'Book an Appointment', href: '/patient/appointments/book' }],
};