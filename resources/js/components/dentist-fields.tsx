import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

export type DaySchedule = { day: string; enabled: boolean; start: string; end: string };

export type DentistForm = {
    specialization: string; // comma separated, e.g. "Orthodontics, Braces"
    appointment_limit: string;
    schedule: DaySchedule[];
};

type SavedDetails = {
    specialization: string[];
    appointment_limit: number;
    available_schedule: { day: string; start: string; end: string }[];
} | null | undefined;

export function buildDentistForm(saved?: SavedDetails): DentistForm {
    return {
        specialization: saved?.specialization?.join(', ') ?? '',
        appointment_limit: saved ? String(saved.appointment_limit) : '',
        schedule: DAYS.map((day) => {
            const found = saved?.available_schedule?.find((s) => s.day === day);
            return {
                day,
                enabled: !!found,
                start: found?.start ?? '09:00',
                end: found?.end ?? '17:00',
            };
        }),
    };
}

// Only the checked days are sent to the server.
export function dentistPayload(d: DentistForm) {
    return {
        specialization: d.specialization,
        appointment_limit: d.appointment_limit,
        schedule: d.schedule
            .filter((s) => s.enabled)
            .map(({ day, start, end }) => ({ day, start, end })),
    };
}

type Props = {
    value: DentistForm;
    onChange: (value: DentistForm) => void;
    errors: Record<string, string>;
};

export default function DentistFields({ value, onChange, errors }: Props) {
    const setDay = (index: number, patch: Partial<DaySchedule>) =>
        onChange({
            ...value,
            schedule: value.schedule.map((s, i) => (i === index ? { ...s, ...patch } : s)),
        });

    const scheduleErrors = Object.entries(errors).filter(([key]) =>
        key.startsWith('dentist.schedule'),
    );

    return (
        <div className="space-y-4 rounded-lg border border-[#f0ddc8] bg-[#fffaf4] p-4">
            <h2 className="text-sm font-bold text-ink">Dentist details</h2>

            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                    <Label htmlFor="specialization" className="font-semibold text-ink">
                        Specialization
                    </Label>
                    <Input
                        id="specialization"
                        placeholder="e.g. Orthodontics, Braces"
                        value={value.specialization}
                        onChange={(e) => onChange({ ...value, specialization: e.target.value })}
                        className="border-[#e0d0c0]"
                    />
                    <p className="text-xs text-gray-500">Separate multiple with commas.</p>
                    {errors['dentist.specialization'] && (
                        <p className="text-sm text-danger">{errors['dentist.specialization']}</p>
                    )}
                </div>

                <div className="space-y-1">
                    <Label htmlFor="appointment_limit" className="font-semibold text-ink">
                        Appointment limit per day
                    </Label>
                    <Input
                        id="appointment_limit"
                        type="number"
                        min={1}
                        placeholder="e.g. 2"
                        value={value.appointment_limit}
                        onChange={(e) => onChange({ ...value, appointment_limit: e.target.value })}
                        className="border-[#e0d0c0]"
                    />
                    <p className="text-xs text-gray-500">
                        Warning only. Bookings above this are still allowed.
                    </p>
                    {errors['dentist.appointment_limit'] && (
                        <p className="text-sm text-danger">{errors['dentist.appointment_limit']}</p>
                    )}
                </div>
            </div>

            <div className="space-y-2">
                <Label className="font-semibold text-ink">Available days and hours</Label>

                {value.schedule.map((s, i) => (
                    <div key={s.day} className="flex items-center gap-3">
                        <label className="flex w-32 items-center gap-2 text-sm capitalize">
                            <input
                                type="checkbox"
                                checked={s.enabled}
                                onChange={(e) => setDay(i, { enabled: e.target.checked })}
                            />
                            {s.day}
                        </label>
                        <Input
                            type="time"
                            value={s.start}
                            disabled={!s.enabled}
                            onChange={(e) => setDay(i, { start: e.target.value })}
                            className="w-32 border-[#e0d0c0]"
                        />
                        <span className="text-sm text-gray-500">to</span>
                        <Input
                            type="time"
                            value={s.end}
                            disabled={!s.enabled}
                            onChange={(e) => setDay(i, { end: e.target.value })}
                            className="w-32 border-[#e0d0c0]"
                        />
                    </div>
                ))}

                {errors['dentist.schedule'] && (
                    <p className="text-sm text-danger">{errors['dentist.schedule']}</p>
                )}
                {scheduleErrors
                    .filter(([key]) => key !== 'dentist.schedule')
                    .map(([key, message]) => (
                        <p key={key} className="text-sm text-danger">{message}</p>
                    ))}
            </div>
        </div>
    );
}