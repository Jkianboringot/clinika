<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\DentistDetail;
use App\Models\MedicalQuestionnaire;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use App\Services\DentistAvailability;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;

// Fixed dates so every edge is deterministic. 2026-11-16 is a Monday; 2026-11-21 is a Saturday.
const AVB_MONDAY = '2026-11-16';
const AVB_SATURDAY = '2026-11-21';

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00'));

    $this->availability = app(DentistAvailability::class);
    $this->dentist = avbDentist(limit: 3);
    $this->procedure = Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800, 'duration' => 30]);
    $this->patient = avbPatient();
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Monday to Friday, 09:00 to 17:00. Pass withProfile false for a dentist with no schedule. */
function avbDentist(int $limit = 3, string $name = 'Dr Cruz', bool $withProfile = true): User
{
    $dentist = User::factory()->create(['name' => $name, 'is_active' => IsActiveEnum::ACTIVE->value]);
    $dentist->assignRole('dentist');

    if ($withProfile) {
        DentistDetail::create([
            'user_id' => $dentist->id,
            'specialization' => ['Braces'],
            'available_schedule' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
                ->map(fn ($day) => ['day' => $day, 'start' => '09:00', 'end' => '17:00'])
                ->all(),
            'appointment_limit' => $limit,
        ]);
    }

    return $dentist;
}

function avbPatient(string $email = 'availability.patient@example.com'): Patient
{
    return Patient::create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1985-03-12',
        'email' => $email,
        'contact_number' => '09171234567',
        'password' => 'MyPassword123',
    ]);
}

/** Creates a booking directly. Defaults to a live, approved, scheduled booking on AVB_MONDAY. */
function avbBook(Patient $patient, Procedure $procedure, User $dentist, string $time, array $overrides = []): Appointment
{
    return Appointment::create(array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'procedure_id' => $procedure->id,
        'appointment_date' => AVB_MONDAY,
        'appointment_time' => $time,
        'status' => 'scheduled',
        'is_approve' => 'approved',
    ], $overrides));
}

/** Every bookable time on a Monday from 09:00 to 16:30, which is all 16 slots. */
function avbAllSlots(): array
{
    $times = [];
    for ($t = Carbon::createFromTime(9, 0); $t->lt(Carbon::createFromTime(17, 0)); $t->addMinutes(30)) {
        $times[] = $t->format('H:i');
    }

    return $times;
}

// ---------------------------------------------------------------------------
// the daily limit
// ---------------------------------------------------------------------------

it('allows a booking while the day is still under the limit', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '11:00'))->toBeNull();
});

it('blocks the booking that would go over a limit of three', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00');

    $conflict = $this->availability->conflict($this->dentist, AVB_MONDAY, '13:00');

    expect($conflict['code'])->toBe('day_full');
    expect($conflict['field'])->toBe('appointment_date');
});

it('limit of one allows one booking and blocks the second', function () {
    $dentist = avbDentist(limit: 1, name: 'Dr One');

    expect($this->availability->conflict($dentist, AVB_MONDAY, '09:00'))->toBeNull();

    avbBook($this->patient, $this->procedure, $dentist, '09:00:00');

    expect($this->availability->conflict($dentist, AVB_MONDAY, '10:00')['code'])->toBe('day_full');
});

it('does not count a cancelled appointment toward the limit', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00', ['status' => 'cancelled']);

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '13:00'))->toBeNull();
});

it('counts a rescheduled appointment toward the limit', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00', ['status' => 'rescheduled']);

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '13:00')['code'])->toBe('day_full');
});

it('counts a pending patient booking toward the limit', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00', ['is_approve' => 'pending']);

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '13:00')['code'])->toBe('day_full');
});

it('does not count the appointment being edited against its own day', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    $editing = avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00');

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '13:00', $editing->id))->toBeNull();
});

it('counts each dentist\'s limit separately, so a full dentist does not block another', function () {
    $other = avbDentist(limit: 3, name: 'Dr Reyes');

    avbBook($this->patient, $this->procedure, $this->dentist, '09:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');
    avbBook($this->patient, $this->procedure, $this->dentist, '11:00:00');

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '13:00')['code'])->toBe('day_full');
    expect($this->availability->conflict($other, AVB_MONDAY, '13:00'))->toBeNull();
});

// ---------------------------------------------------------------------------
// the slots
// ---------------------------------------------------------------------------

it('allows the same time with a different dentist', function () {
    $other = avbDentist(limit: 3, name: 'Dr Reyes');
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    expect($this->availability->conflict($other, AVB_MONDAY, '10:00'))->toBeNull();
});

it('refuses a time between the 30-minute steps', function () {
    $conflict = $this->availability->conflict($this->dentist, AVB_MONDAY, '09:15');

    expect($conflict['code'])->toBe('outside_hours');
    expect($conflict['field'])->toBe('appointment_time');
});

it('allows the first slot at 09:00 and refuses 08:30, which is before opening', function () {
    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '09:00'))->toBeNull();
    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '08:30')['code'])->toBe('outside_hours');
});

it('allows the last slot at 16:30 and refuses 17:00, which is closing time', function () {
    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '16:30'))->toBeNull();
    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '17:00')['code'])->toBe('outside_hours');
});

it('refuses a time already booked with the time_taken code', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    $conflict = $this->availability->conflict($this->dentist, AVB_MONDAY, '10:00');

    expect($conflict['code'])->toBe('time_taken');
    expect($conflict['field'])->toBe('appointment_time');
});

it('treats 10:00:00 and 10:00 as the same time', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '10:00:00')['code'])->toBe('time_taken');
});

it('reopens a slot once its booking is cancelled', function () {
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00', ['status' => 'cancelled']);

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '10:00'))->toBeNull();
});

// ---------------------------------------------------------------------------
// past dates and times, with a fixed clock
// ---------------------------------------------------------------------------

it('refuses a date before today', function () {
    $conflict = $this->availability->conflict($this->dentist, '2026-11-13', '10:00');

    expect($conflict['code'])->toBe('past');
    expect($conflict['field'])->toBe('appointment_date');
});

it('refuses a time earlier today than the current time', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '09:00')['code'])->toBe('past');
});

it('allows a later time today than the current time', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));

    expect($this->availability->conflict($this->dentist, AVB_MONDAY, '10:00'))->toBeNull();
});

// ---------------------------------------------------------------------------
// days off and dentists without a schedule
// ---------------------------------------------------------------------------

it('refuses a Saturday, which is a day off', function () {
    $conflict = $this->availability->conflict($this->dentist, AVB_SATURDAY, '10:00');

    expect($conflict['code'])->toBe('day_off');
    expect($conflict['field'])->toBe('appointment_date');
});

it('refuses a dentist who has no schedule set up', function () {
    $noSchedule = avbDentist(withProfile: false, name: 'Dr New');

    $conflict = $this->availability->conflict($noSchedule, AVB_MONDAY, '10:00');

    expect($conflict['code'])->toBe('no_profile');
    expect($conflict['field'])->toBe('dentist_id');
});

// ---------------------------------------------------------------------------
// free times and the calendar colours
// ---------------------------------------------------------------------------

it('lists free times without booked times or times already past today', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));
    avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    $free = $this->availability->freeTimes($this->dentist, AVB_MONDAY);

    expect($free)->not->toContain('09:00');
    expect($free)->not->toContain('09:30');
    expect($free)->not->toContain('10:00');
    expect($free)->toContain('10:30');
    expect($free)->toContain('16:30');
});

it('returns no free times on a day off', function () {
    expect($this->availability->freeTimes($this->dentist, AVB_SATURDAY))->toBe([]);
});

it('keeps the appointment being edited free in its own slot list', function () {
    $booking = avbBook($this->patient, $this->procedure, $this->dentist, '10:00:00');

    expect($this->availability->freeTimes($this->dentist, AVB_MONDAY, $booking->id))->toContain('10:00');
});

it('shows a day as full when every time is booked, even under the limit', function () {
    $dentist = avbDentist(limit: 99, name: 'Dr Busy');

    foreach (avbAllSlots() as $time) {
        avbBook($this->patient, $this->procedure, $dentist, $time . ':00');
    }

    $statuses = $this->availability->monthStatuses('2026-11', $dentist->id);

    expect($statuses[AVB_MONDAY])->toBe('full');
});

it('shows a day as available while at least one time is free', function () {
    $statuses = $this->availability->monthStatuses('2026-11', $this->dentist->id);

    expect($statuses[AVB_MONDAY])->toBe('available');
});

it('shows weekends as unavailable and earlier days in the month as past', function () {
    $statuses = $this->availability->monthStatuses('2026-11', $this->dentist->id);

    expect($statuses[AVB_SATURDAY])->toBe('unavailable');
    expect($statuses['2026-11-10'])->toBe('past');
});

// ---------------------------------------------------------------------------
// the patient booking limit, end to end
// ---------------------------------------------------------------------------

it('lets a patient book up to the limit and refuses the booking past it', function () {
    $this->actingAs($this->patient, 'patient');

    $questionnaire = [
        'answers' => collect(MedicalQuestionnaire::sections()['general'])
            ->pluck('key')
            ->mapWithKeys(fn ($k) => [$k => false])
            ->all(),
        'conditions' => [],
        'agreed' => true,
    ];

    $book = fn (string $time) => $this->withSession(['pending_questionnaire' => $questionnaire])
        ->post(route('patient.appointments.store'), [
            'procedure_id' => $this->procedure->id,
            'dentist_id' => $this->dentist->id,
            'appointment_date' => AVB_MONDAY,
            'appointment_time' => $time,
        ]);

    $book('09:00')->assertSessionHasNoErrors();
    $book('10:00')->assertSessionHasNoErrors();
    $book('11:00')->assertSessionHasNoErrors();

    $book('13:00')->assertSessionHasErrors('appointment_date');
    $this->assertDatabaseCount('appointments', 3);
});