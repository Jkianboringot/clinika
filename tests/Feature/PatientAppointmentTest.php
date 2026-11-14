<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\DentistDetail;
use App\Models\MedicalQuestionnaire;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Cache::flush();

    $this->procedure = Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800, 'duration' => 30]);
    $this->dentist = patApptDentist(limit: 2);
    $this->patient = patApptPatient('maria.patient@example.com');
    $this->date = patApptNextMonday();

    $this->actingAs($this->patient, 'patient');
});

/** Next Monday, so every test books a future working day. */
function patApptNextMonday(): string
{
    return Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
}

/** Active dentist working Monday to Friday, 09:00 to 17:00, with a daily limit. */
function patApptDentist(int $limit = 5, string $name = 'Dr Cruz'): User
{
    $dentist = User::factory()->create(['name' => $name, 'is_active' => IsActiveEnum::ACTIVE->value]);
    $dentist->assignRole('dentist');

    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Braces'],
        'available_schedule' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->map(fn ($day) => ['day' => $day, 'start' => '09:00', 'end' => '17:00'])
            ->all(),
        'appointment_limit' => $limit,
    ]);

    return $dentist;
}

/** A patient who is not on the female questionnaire path. */
function patApptPatient(string $email): Patient
{
    $patient = Patient::create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1985-03-12',
        'email' => $email,
        'contact_number' => '09171234567',
        'password' => 'MyPassword123',
    ]);

    foreach (GenderEnum::cases() as $gender) {
        if (! MedicalQuestionnaire::showsWomenSection($patient)) {
            break;
        }
        $patient->update(['gender' => $gender->value]);
        $patient->refresh();
    }

    return $patient;
}

/** A complete, agreed questionnaire in the session shape the patient store expects. */
function patApptQuestionnaire(Patient $patient): array
{
    $sections = MedicalQuestionnaire::sections();
    $keys = collect($sections['general'])->pluck('key');

    if (MedicalQuestionnaire::showsWomenSection($patient)) {
        $keys = $keys->merge(collect($sections['women'])->pluck('key'));
    }

    return [
        'answers' => $keys->mapWithKeys(fn ($k) => [$k => false])->all(),
        'conditions' => [],
        'agreed' => true,
    ];
}

/** Creates an appointment row directly, to set up the state a test needs. */
function patApptAppointment(Patient $patient, User $dentist, Procedure $procedure, array $overrides = []): Appointment
{
    return Appointment::create(array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'procedure_id' => $procedure->id,
        'appointment_date' => patApptNextMonday(),
        'appointment_time' => '09:00:00',
        'status' => 'scheduled',
        'is_approve' => 'approved',
    ], $overrides));
}

/** A valid patient booking payload. */
function patApptPayload(User $dentist, Procedure $procedure, array $overrides = []): array
{
    return array_merge([
        'procedure_id' => $procedure->id,
        'dentist_id' => $dentist->id,
        'appointment_date' => patApptNextMonday(),
        'appointment_time' => '09:00',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// the patient's own list
// ---------------------------------------------------------------------------

it('lists only the patient\'s own appointments', function () {
    $other = patApptPatient('other.patient@example.com');
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);
    patApptAppointment($other, $this->dentist, $this->procedure, ['appointment_time' => '10:00:00']);

    $response = $this->get(route('patient.appointments.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Appointment/Index')
        ->has('appointments.data', 1)
        ->where('appointments.data.0.appointment_time', '09:00:00')
    );
});

it('shows a pending booking to the patient while it waits for approval', function () {
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['is_approve' => 'pending']);

    $response = $this->get(route('patient.appointments.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('appointments.data', 1)
        ->where('appointments.data.0.is_approve', 'pending')
    );
});

// ---------------------------------------------------------------------------
// the calendar and time list
// ---------------------------------------------------------------------------

it('colours a full day red for the patient calendar', function () {
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '10:00:00']);

    $response = $this->get(route('patient.appointments.day_statuses', [
        'month' => Carbon::parse($this->date)->format('Y-m'),
        'dentist_id' => $this->dentist->id,
    ]));

    expect($response->json('days')[$this->date])->toBe('full');
});

it('lists only free times for the patient, with booked times removed', function () {
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);

    $response = $this->get(route('patient.appointments.open_slots', [
        'date' => $this->date,
        'dentist_id' => $this->dentist->id,
    ]));

    expect($response->json('times'))->not->toContain('09:00');
    expect($response->json('times'))->toContain('09:30');
});

// ---------------------------------------------------------------------------
// booking rules
// ---------------------------------------------------------------------------

it('refuses a patient booking once the dentist\'s daily limit is reached', function () {
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);
    patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '10:00:00']);

    $response = $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure, [
            'appointment_time' => '11:00',
        ]));

    $response->assertSessionHasErrors('appointment_date');
    $this->assertDatabaseCount('appointments', 2);
});

it('refuses a patient booking on a past date', function () {
    $response = $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure, [
            'appointment_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        ]));

    $response->assertSessionHasErrors('appointment_date');
    $this->assertDatabaseCount('appointments', 0);
});

it('refuses a patient booking on a time another patient already holds', function () {
    $other = patApptPatient('other.patient@example.com');
    patApptAppointment($other, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);

    $response = $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure));

    $response->assertSessionHasErrors('appointment_time');
    $this->assertDatabaseCount('appointments', 1);
});

it('refuses a patient booking outside the dentist\'s working hours', function () {
    $response = $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure, [
            'appointment_time' => '19:00',
        ]));

    $response->assertSessionHasErrors('appointment_time');
    $this->assertDatabaseCount('appointments', 0);
});

it('ignores a status or approval sent by the patient, so a booking cannot approve itself', function () {
    $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure, [
            'is_approve' => 'approved',
            'status' => 'completed',
        ]));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $this->patient->id,
        'status' => 'scheduled',
        'is_approve' => 'pending',
    ]);
});

it('keeps the patient booking description', function () {
    $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure, [
            'description' => 'Sensitive gums on the left side',
        ]));

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $this->patient->id,
        'description' => 'Sensitive gums on the left side',
    ]);
});

it('does not show a patient\'s pending booking in the staff approved list', function () {
    $this->withSession(['pending_questionnaire' => patApptQuestionnaire($this->patient)])
        ->post(route('patient.appointments.store'), patApptPayload($this->dentist, $this->procedure));

    $staff = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $staff->assignRole('staff');
    $this->actingAs($staff);

    $response = $this->get(route('appointments.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('appointments.data', 0));
});

// ---------------------------------------------------------------------------
// reschedule
// ---------------------------------------------------------------------------

it('opens the reschedule page filled with the current booking', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure);

    $response = $this->get(route('patient.appointments.edit', $appointment));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Appointment/Create')
        ->where('appointment.id', $appointment->id)
        ->where('appointment.appointment_time', '09:00')
    );
});

it('moves a patient booking to another dentist and sends it for reschedule approval', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure, ['is_approve' => 'approved']);
    $otherDentist = patApptDentist(limit: 5, name: 'Dr Reyes');

    $this->put(route('patient.appointments.update', $appointment), patApptPayload($otherDentist, $this->procedure, [
        'appointment_time' => '09:00',
    ]));

    $appointment->refresh();
    expect($appointment->dentist_id)->toBe($otherDentist->id);
    expect($appointment->status)->toBe('rescheduled');
    expect($appointment->is_approve)->toBe('pending_reschedule');
});

it('refuses a reschedule into a time already taken', function () {
    $other = patApptPatient('other.patient@example.com');
    patApptAppointment($other, $this->dentist, $this->procedure, ['appointment_time' => '10:00:00']);
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure, ['appointment_time' => '09:00:00']);

    $response = $this->put(route('patient.appointments.update', $appointment), patApptPayload($this->dentist, $this->procedure, [
        'appointment_time' => '10:00',
    ]));

    $response->assertSessionHasErrors('appointment_time');
    expect($appointment->fresh()->appointment_time)->toBe('09:00:00');
});

it('refuses to reschedule an appointment that already has a treatment', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure);
    Treatment::create([
        'appointment_id' => $appointment->id,
        'user_id' => $this->dentist->id,
        'duration_minutes' => 30,
        'summary' => 'Done',
        'follow_up_schedule' => now()->addWeek()->format('Y-m-d'),
    ]);

    $response = $this->put(route('patient.appointments.update', $appointment), patApptPayload($this->dentist, $this->procedure, [
        'appointment_time' => '10:00',
    ]));

    $response->assertSessionHas('error');
    expect($appointment->fresh()->appointment_time)->toBe('09:00:00');
});

it('refuses to reschedule a cancelled appointment', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure, ['status' => 'cancelled']);

    $response = $this->put(route('patient.appointments.update', $appointment), patApptPayload($this->dentist, $this->procedure, [
        'appointment_time' => '10:00',
    ]));

    $response->assertSessionHas('error');
    expect($appointment->fresh()->appointment_time)->toBe('09:00:00');
});

// ---------------------------------------------------------------------------
// cancel
// ---------------------------------------------------------------------------

it('cancelling an approved booking keeps the row and frees the slot', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure, ['is_approve' => 'approved']);

    $this->delete(route('patient.appointments.cancel', $appointment));

    $appointment->refresh();
    expect($appointment->status)->toBe('cancelled');
    expect($appointment->is_approve)->toBe('approved');
});

it('refuses to cancel an appointment that already has a treatment', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure);
    Treatment::create([
        'appointment_id' => $appointment->id,
        'user_id' => $this->dentist->id,
        'duration_minutes' => 30,
        'summary' => 'Done',
        'follow_up_schedule' => now()->addWeek()->format('Y-m-d'),
    ]);

    $this->delete(route('patient.appointments.cancel', $appointment));

    expect($appointment->fresh()->status)->toBe('scheduled');
});

// ---------------------------------------------------------------------------
// questionnaire from an appointment
// ---------------------------------------------------------------------------

it('opens the questionnaire from one of the patient\'s own appointments', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure);

    $response = $this->get(route('patient.appointments.questionnaire.edit', $appointment));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Appointment/Questionnaire')
        ->where('appointment.id', $appointment->id)
    );
});

it('saves a questionnaire edited from an appointment to the patient\'s record', function () {
    $appointment = patApptAppointment($this->patient, $this->dentist, $this->procedure);
    $answers = patApptQuestionnaire($this->patient)['answers'];
    $firstKey = array_key_first($answers);
    $answers[$firstKey] = true;

    $this->put(route('patient.appointments.questionnaire.update', $appointment), [
        'answers' => $answers,
        'conditions' => [],
        'agreed' => true,
    ])->assertSessionHasNoErrors();

    $record = MedicalQuestionnaire::where('patient_id', $this->patient->id)->firstOrFail();
    expect($record->answers[$firstKey])->toBeTrue();
});

it('does not open another patient\'s appointment questionnaire', function () {
    $other = patApptPatient('other.patient@example.com');
    $appointment = patApptAppointment($other, $this->dentist, $this->procedure);

    $this->get(route('patient.appointments.questionnaire.edit', $appointment))->assertNotFound();
});

// ---------------------------------------------------------------------------
// patients cannot reach staff pages
// ---------------------------------------------------------------------------

it('keeps a patient out of the staff appointment list', function () {
    $response = $this->get(route('appointments.index'));

    $response->assertRedirect(route('login'));
});

it('keeps a patient from booking through the staff store', function () {
    $response = $this->post(route('appointments.store'), patApptPayload($this->dentist, $this->procedure));

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('appointments', 0);
});