import { useMemo } from 'react';
import { cn } from '@/lib/utils';

interface TimeSlotsProps {
    value: string; // "HH:mm" or ""
    onChange: (value: string) => void;
    startHour?: number; // first slot, default 8 (8:00 AM)
    endHour?: number; // last slot ends before this hour, default 18 (6:00 PM)
    stepMinutes?: number; // default 30
    className?: string;
}

function toLabel(hhmm: string) {
    const [h, m] = hhmm.split(':').map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return '';
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 === 0 ? 12 : h % 12;
    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
}

export function formatTime(hhmm: string) {
    return hhmm ? toLabel(hhmm) : '';
}

export function TimeSlots({
    value,
    onChange,
    startHour = 8,
    endHour = 18,
    stepMinutes = 30,
    className,
}: TimeSlotsProps) {
    const slots = useMemo(() => {
        const list: string[] = [];
        for (let mins = startHour * 60; mins < endHour * 60; mins += stepMinutes) {
            const h = String(Math.floor(mins / 60)).padStart(2, '0');
            const m = String(mins % 60).padStart(2, '0');
            list.push(`${h}:${m}`);
        }
        // Keep an existing time visible even if it falls outside the slot range
        // (e.g. an older appointment booked at 7:15).
        if (value && !list.includes(value)) {
            list.push(value);
            list.sort();
        }
        return list;
    }, [value, startHour, endHour, stepMinutes]);

    return (
        <div className={cn('grid grid-cols-4 gap-2', className)}>
            {slots.map((slot) => {
                const isSelected = slot === value;

                return (
                    <button
                        key={slot}
                        type="button"
                        onClick={() => onChange(slot)}
                        className={cn(
                            'h-9 rounded-lg border text-xs font-medium whitespace-nowrap transition-colors',
                            isSelected
                                ? 'border-blue-600 bg-blue-600 font-semibold text-white hover:bg-blue-700'
                                : 'border-gray-200 bg-white text-ink hover:border-blue-400 hover:bg-blue-50',
                        )}
                    >
                        {toLabel(slot)}
                    </button>
                );
            })}
        </div>
    );
}