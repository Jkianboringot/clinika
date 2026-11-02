<?php

namespace App\Services;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Single source of truth for when a dentist can take an appointment.
 *
 * Every check returns a reason as ['field', 'code', 'message'] so the form can
 * show the problem under the right input:
 *   field "dentist_id"       -> no schedule set up for this dentist
 *   field "appointment_date" -> past date, day off, or daily limit reached
 *   field "appointment_time" -> outside working hours, past time, or already booked
 */
class DentistAvailability
{
    /** Must match the TimeSlots component on the frontend. */
    public const STEP_MINUTES = 30;

    /** Active dentists, optionally a single one. */
    public function dentists(?int $dentistId = null): Collection
    {
        return User::role('dentist')
            ->where('is_active', IsActiveEnum::ACTIVE->value)
            ->when($dentistId, fn ($q) => $q->whereKey($dentistId))
            ->with('dentistDetail')
            ->orderBy('name')
            ->get();
    }

    /** Working windows for the weekday of $date: [['start' => '09:00', 'end' => '17:00'], ...] */
    public function workingWindows(User $dentist, string $date): array
    {
        $details = $dentist->dentistDetail;

        if (! $details) {
            return [];
        }

        $day = strtolower(Carbon::parse($date)->englishDayOfWeek);
        $windows = [];

        foreach ($details->available_schedule ?? [] as $window) {
            if (strtolower($window['day'] ?? '') !== $day) {
                continue;
            }

            $start = substr((string) ($window['start'] ?? ''), 0, 5);
            $end = substr((string) ($window['end'] ?? ''), 0, 5);

            if ($start === '' || $end === '') {
                continue;
            }

            $windows[] = ['start' => $start, 'end' => $end];
        }

        return $windows;
    }

    /** Every bookable "HH:mm" on the weekday of $date, before bookings are removed. */
    public function workingTimes(User $dentist, string $date): array
    {
        $times = [];

        foreach ($this->workingWindows($dentist, $date) as $window) {
            $end = Carbon::createFromFormat('H:i', $window['end']);

            for (
                $t = Carbon::createFromFormat('H:i', $window['start']);
                $t->lt($end);
                $t->addMinutes(self::STEP_MINUTES)
            ) {
                $times[] = $t->format('H:i');
            }
        }

        $times = array_values(array_unique($times));
        sort($times);

        return $times;
    }

    /** Active (non-cancelled) appointments for a dentist on a date. Cancelling frees both the slot and the daily count. */
    private function bookings(int $dentistId, string $date, ?int $ignoreId): Collection
    {
        return Appointment::where('dentist_id', $dentistId)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', AppointmentStatusEnum::Cancelled->value)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get(['id', 'appointment_time']);
    }

    private function block(string $field, string $code, string $message): array
    {
        return compact('field', 'code', 'message');
    }

    /** Why the whole day can't be booked for this dentist, or null when it can. */
    private function dayBlock(User $dentist, string $date, ?int $ignoreId): ?array
    {
        $name = $dentist->name;
        $weekday = Carbon::parse($date)->format('l');

        if (! $dentist->dentistDetail) {
            return $this->block('dentist_id', 'no_profile', "{$name} has no schedule set up yet.");
        }

        if (Carbon::parse($date)->lt(now()->startOfDay())) {
            return $this->block('appointment_date', 'past', 'Past dates cannot be booked.');
        }

        if ($this->workingWindows($dentist, $date) === []) {
            return $this->block('appointment_date', 'day_off', "{$name} does not work on {$weekday}s.");
        }

        $limit = (int) $dentist->dentistDetail->appointment_limit;
        $count = $this->bookings($dentist->id, $date, $ignoreId)->count();

        if ($count >= $limit) {
            return $this->block(
                'appointment_date',
                'day_full',
                "{$name} is fully booked on this day ({$count} of {$limit} appointments).",
            );
        }

        return null;
    }

    /** Why one time on a day can't be booked, or null when it can. Assumes the day itself is open. */
    private function timeBlock(User $dentist, string $date, string $time, ?int $ignoreId): ?array
    {
        $name = $dentist->name;
        $label = Carbon::createFromFormat('H:i', $time)->format('g:i A');

        if (! in_array($time, $this->workingTimes($dentist, $date), true)) {
            $weekday = Carbon::parse($date)->format('l');
            $windows = collect($this->workingWindows($dentist, $date))
                ->map(fn ($w) => Carbon::createFromFormat('H:i', $w['start'])->format('g:i A')
                    . ' to '
                    . Carbon::createFromFormat('H:i', $w['end'])->format('g:i A'))
                ->implode(', ');

            return $this->block(
                'appointment_time',
                'outside_hours',
                "{$label} is outside {$name}'s hours on {$weekday}s ({$windows}).",
            );
        }

        if (Carbon::parse("{$date} {$time}")->lte(now())) {
            return $this->block('appointment_time', 'past', 'That time has already passed.');
        }

        $taken = $this->bookings($dentist->id, $date, $ignoreId)
            ->contains(fn ($a) => substr((string) $a->appointment_time, 0, 5) === $time);

        if ($taken) {
            return $this->block('appointment_time', 'time_taken', "{$name} already has an appointment at {$label}.");
        }

        return null;
    }

    /**
     * Null when the slot can be booked, otherwise ['field', 'code', 'message'].
     * The day rules run first, then the time rules.
     */
    public function conflict(User $dentist, string $date, string $time, ?int $ignoreId = null): ?array
    {
        $time = substr($time, 0, 5);

        return $this->dayBlock($dentist, $date, $ignoreId)
            ?? $this->timeBlock($dentist, $date, $time, $ignoreId);
    }

    /** Times ("HH:mm") a dentist can still take on a date. Empty when the day is blocked. */
    public function freeTimes(User $dentist, string $date, ?int $ignoreId = null): array
    {
        if ($this->dayBlock($dentist, $date, $ignoreId) !== null) {
            return [];
        }

        $taken = $this->bookings($dentist->id, $date, $ignoreId)
            ->map(fn ($a) => substr((string) $a->appointment_time, 0, 5))
            ->all();

        $now = now();

        return array_values(array_filter(
            $this->workingTimes($dentist, $date),
            fn ($time) => ! in_array($time, $taken, true)
                && Carbon::parse("{$date} {$time}")->gt($now),
        ));
    }

    /** First dentist (in name order) who can take the slot. Used when the patient has no preferred dentist. */
    public function pickDentist(string $date, string $time, ?int $dentistId = null): ?User
    {
        $time = substr($time, 0, 5);

        return $this->dentists($dentistId)->first(
            fn (User $d) => in_array($time, $this->freeTimes($d, $date), true)
        );
    }

    /**
     * Calendar status for every day of a month, keyed by "Y-m-d":
     * available | full | unavailable | past
     * Pass $ignoreId when editing so the appointment's own booking doesn't count against its day.
     */
    public function monthStatuses(string $month, ?int $dentistId = null, ?int $ignoreId = null): array
    {
        $dentists = $this->dentists($dentistId);
        $cursor = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $today = now()->startOfDay();
        $statuses = [];

        for ($day = $cursor->copy(); $day->month === $cursor->month; $day->addDay()) {
            $date = $day->toDateString();

            if ($day->lt($today)) {
                $statuses[$date] = 'past';
                continue;
            }

            $open = $dentists->contains(
                fn (User $d) => $this->freeTimes($d, $date, $ignoreId) !== []
            );

            if ($open) {
                $statuses[$date] = 'available';
                continue;
            }

            $worksThatDay = $dentists->contains(
                fn (User $d) => $d->dentistDetail && $this->workingWindows($d, $date) !== []
            );

            $statuses[$date] = $worksThatDay ? 'full' : 'unavailable';
        }

        return $statuses;
    }
}