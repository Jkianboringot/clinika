import { useMemo, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DateCalendarProps {
    value: string; // "YYYY-MM-DD" or ""
    onChange: (value: string) => void;
    minDate?: string; // "YYYY-MM-DD" — days before this are disabled
    className?: string;
}

const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

// Local date parts so timezones don't shift the day.
function toIso(d: Date) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function parseIso(s: string): Date | null {
    if (!s) return null;
    const [y, m, d] = s.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

export function formatIsoDate(s: string) {
    const d = parseIso(s);
    if (!d) return '';
    return d.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function sameDay(a: Date, b: Date) {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

// 6 weeks x 7 days, starting from the Sunday before the 1st of the month.
function buildGrid(year: number, month: number): Date[] {
    const first = new Date(year, month, 1);
    const start = new Date(year, month, 1 - first.getDay());

    return Array.from(
        { length: 42 },
        (_, i) =>
            new Date(start.getFullYear(), start.getMonth(), start.getDate() + i),
    );
}

export function DateCalendar({
    value,
    onChange,
    minDate,
    className,
}: DateCalendarProps) {
    const today = useMemo(() => {
        const t = new Date();
        t.setHours(0, 0, 0, 0);
        return t;
    }, []);

    const selected = parseIso(value);
    const min = parseIso(minDate ?? '');

    const [view, setView] = useState<Date>(selected ?? today);

    const grid = useMemo(
        () => buildGrid(view.getFullYear(), view.getMonth()),
        [view],
    );

    const monthLabel = view.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });

    const shiftMonth = (n: number) => {
        setView(new Date(view.getFullYear(), view.getMonth() + n, 1));
    };

    const pick = (d: Date) => onChange(toIso(d));

    return (
        <div className={cn('w-full', className)}>
            {/* Header: prev / month / next */}
            <div className="mb-3 flex items-center justify-between">
                <button
                    type="button"
                    onClick={() => shiftMonth(-1)}
                    className="flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-50"
                    aria-label="Previous month"
                >
                    <ChevronLeft className="h-4 w-4" />
                </button>

                <span className="text-sm font-semibold text-ink">{monthLabel}</span>

                <button
                    type="button"
                    onClick={() => shiftMonth(1)}
                    className="flex h-8 w-8 items-center justify-center rounded-full text-blue-600 hover:bg-blue-50"
                    aria-label="Next month"
                >
                    <ChevronRight className="h-4 w-4" />
                </button>
            </div>

            {/* Weekday labels */}
            <div className="grid grid-cols-7 pb-1">
                {WEEKDAYS.map((w) => (
                    <span key={w} className="text-center text-xs font-medium text-subtle">
                        {w}
                    </span>
                ))}
            </div>

            {/* Days */}
            <div className="grid grid-cols-7 gap-y-1">
                {grid.map((d) => {
                    const isOutside = d.getMonth() !== view.getMonth();
                    const isSelected = !!selected && sameDay(d, selected);
                    const isToday = sameDay(d, today);
                    const isDisabled = !!min && d < min;

                    return (
                        <button
                            key={d.toISOString()}
                            type="button"
                            disabled={isDisabled}
                            onClick={() => pick(d)}
                            className={cn(
                                'mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition-colors',
                                isOutside && 'text-gray-300',
                                !isOutside && !isSelected && !isDisabled && 'text-ink hover:bg-blue-50',
                                isToday &&
                                    !isSelected &&
                                    'font-semibold text-blue-600 ring-1 ring-blue-600',
                                isSelected &&
                                    'bg-blue-600 font-semibold text-white hover:bg-blue-700',
                                isDisabled &&
                                    'cursor-not-allowed text-gray-300 hover:bg-transparent',
                            )}
                        >
                            {d.getDate()}
                        </button>
                    );
                })}
            </div>

            {/* Footer */}
            <div className="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
                <button
                    type="button"
                    onClick={() => onChange('')}
                    className="text-sm font-medium text-subtle hover:text-ink"
                >
                    Clear
                </button>

                <button
                    type="button"
                    onClick={() => {
                        setView(today);
                        pick(today);
                    }}
                    disabled={!!min && today < min}
                    className="text-sm font-semibold text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:text-gray-300"
                >
                    Today
                </button>
            </div>
        </div>
    );
}