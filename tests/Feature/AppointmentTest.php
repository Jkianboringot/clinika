<?php

use App\Enums\GenderEnum;
use App\Enums\IsApproveEnum;
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

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has appointments.view/create/update/delete
    $this->actingAs($this->user);

    $this->procedure = Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800, 'duration' => 30]);
    $this->dentist = apptTestDentist(limit: 2);
    $this->patient = apptTestPatient();
    $this->date = apptTestDate();
});

/** Next Monday, so the test always books a future working day. */
function apptTestDate(): string
{
    return Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
}

/** An active dentist working Monday to Friday, 09:00 to 17:00, with a daily limit. */
function apptTestDentist(int $limit = 5): User
{
    $dentist = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
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

/** A patient who is not on the female questionnaire path, so the general questions are enough. */
function apptTestPatient(string $email = 'juan.appt@example.com'): Patient
{
    $patient = Patient::create([
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1980-01-01',
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

/** A complete, agreed questionnaire in the session shape the controllers expect. */
function apptTestPending(Patient $patient): array
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

/** Creates an appointment row directly, for setting up the state a test needs. */
function apptTestAppointment(array $overrides = []): Appointment
{
    return Appointment::create(array_merge([
        'patient_id' => test()->patient->id,
        'dentist_id' => test()->dentist->id,
        'procedure_id' => test()->procedure->id,
        'staff_id' => test()->user->id,
        'appointment_date' => test()->date,
        'appointment_time' => '09:00:00',
        'status' => 'scheduled',
        'is_approve' => IsApproveEnum::Approve->value,
    ], $overrides));
}

/** A valid staff booking payload. */
function apptTestStaffPayload(array $overrides = []): array
{
    return array_merge([
        'patient_id' => test()->patient->id,
        'dentist_id' => test()->dentist->id,
        'procedure_id' => test()->procedure->id,
        'appointment_date' => test()->date,
        'appointment_time' => '09:00',
        'description' => null,
    ], $overrides);
}

// ---------------------------------------------------------------------------
// the questionnaire comes first
// ---------------------------------------------------------------------------

it('sends staff to the questionnaire when they open create without answering it', function () {
    $response = $this->get(route('appointments.create'));

    $response->assertRedirect(route('appointments.questionnaire'));
});

it('opens the booking page once the questionnaire is in the session', function () {
    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->get(route('appointments.create'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Appointments/Create'));
});

it('does not book for staff without a questionnaire in the session, even if one is on file', function () {
    MedicalQuestionnaire::create([
        'patient_id' => $this->patient->id,
        'answers' => apptTestPending($this->patient)['answers'],
        'conditions' => [],
        'agreed' => true,
    ]);

    $response = $this->post(route('appointments.store'), apptTestStaffPayload());

    $response->assertRedirect(route('appointments.questionnaire'));
    $this->assertDatabaseCount('appointments', 0);
});

it('skipping the questionnaire as staff stores every answer as no', function () {
    $response = $this->post(route('appointments.questionnaire.skip'));

    $response->assertSessionHas('pending_questionnaire', function ($pending) {
        return collect($pending['answers'])->every(fn ($answer) => $answer === false || $answer === 0);
    });
});

it('does not let a patient skip the questionnaire', function () {
    $this->actingAs($this->patient, 'patient');

    $response = $this->post('/patient/appointments/questionnaire/skip');

    $response->assertNotFound();
});

it('does not save a patient questionnaire until the agreement box is ticked', function () {
    $this->actingAs($this->patient, 'patient');

    $answers = collect(apptTestPending($this->patient)['answers'])->all();

    $response = $this->post(route('patient.appointments.questionnaire.save'), [
        'answers' => $answers,
        'conditions' => [],
        'agreed' => false,
    ]);

    $response->assertSessionHasErrors('agreed');
});

// ---------------------------------------------------------------------------
// staff and patient booking
// ---------------------------------------------------------------------------

it('books for staff as approved and scheduled, then clears the questionnaire from the session', function () {
    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload());

    $response->assertRedirect(route('appointments.index'));
    $this->assertDatabaseHas('appointments', [
        'patient_id' => $this->patient->id,
        'dentist_id' => $this->dentist->id,
        'status' => 'scheduled',
        'is_approve' => IsApproveEnum::Approve->value,
    ]);
});

it('books for a patient as pending, waiting for staff approval', function () {
    $this->actingAs($this->patient, 'patient');

    $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('patient.appointments.store'), [
            'procedure_id' => $this->procedure->id,
            'dentist_id' => $this->dentist->id,
            'appointment_date' => $this->date,
            'appointment_time' => '09:00',
        ]);

    $this->assertDatabaseHas('appointments', [
        'patient_id' => $this->patient->id,
        'status' => 'scheduled',
        'is_approve' => IsApproveEnum::Pending->value,
    ]);
});

it('refuses a booking outside the dentist\'s working hours', function () {
    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_time' => '20:00']));

    $response->assertSessionHasErrors('appointment_time');
    $this->assertDatabaseCount('appointments', 0);
});

it('refuses a booking on a day the dentist does not work', function () {
    $sunday = Carbon::parse($this->date)->next(Carbon::SUNDAY)->format('Y-m-d');

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_date' => $sunday]));

    $response->assertSessionHasErrors('appointment_date');
    $this->assertDatabaseCount('appointments', 0);
});

it('refuses a booking on a past date', function () {
    $past = Carbon::now()->subDays(3)->format('Y-m-d');

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_date' => $past]));

    $response->assertSessionHasErrors('appointment_date');
});

it('refuses a time that is already booked for that dentist', function () {
    apptTestAppointment();

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload());

    $response->assertSessionHasErrors('appointment_time');
    $this->assertDatabaseCount('appointments', 1);
});

// ---------------------------------------------------------------------------
// the daily limit
// ---------------------------------------------------------------------------

it('refuses a booking once the dentist has reached the daily limit', function () {
    apptTestAppointment(['appointment_time' => '09:00:00']);
    apptTestAppointment(['appointment_time' => '10:00:00']);

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_time' => '11:00']));

    $response->assertSessionHasErrors('appointment_date');
    $this->assertDatabaseCount('appointments', 2);
});

it('frees a day slot when an appointment is cancelled', function () {
    apptTestAppointment(['appointment_time' => '09:00:00']);
    $second = apptTestAppointment(['appointment_time' => '10:00:00']);

    $this->delete(route('appointments.destroy', $second));

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_time' => '11:00']));

    $response->assertSessionHasNoErrors();
});

it('counts a pending patient booking against the daily limit', function () {
    apptTestAppointment(['is_approve' => IsApproveEnum::Pending->value, 'appointment_time' => '09:00:00']);
    apptTestAppointment(['is_approve' => IsApproveEnum::Pending->value, 'appointment_time' => '10:00:00']);

    $response = $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload(['appointment_time' => '11:00']));

    $response->assertSessionHasErrors('appointment_date');
});

it('colours a full day red and an open day blue in the month view', function () {
    apptTestAppointment(['appointment_time' => '09:00:00']);
    apptTestAppointment(['appointment_time' => '10:00:00']);

    $response = $this->get(route('appointments.day_statuses', [
        'month' => Carbon::parse($this->date)->format('Y-m'),
        'dentist_id' => $this->dentist->id,
    ]));

    $days = $response->json('days');
    expect($days[$this->date])->toBe('full');

    $nextMonday = Carbon::parse($this->date)->addWeek()->format('Y-m-d');
    expect($days[$nextMonday])->toBe('available');
});

it('lists only the free times for the day, with booked times removed', function () {
    apptTestAppointment(['appointment_time' => '09:00:00']);

    $response = $this->get(route('appointments.open_slots', [
        'date' => $this->date,
        'dentist_id' => $this->dentist->id,
    ]));

    expect($response->json('times'))->not->toContain('09:00');
    expect($response->json('times'))->toContain('09:30');
});

// ---------------------------------------------------------------------------
// editing as staff
// ---------------------------------------------------------------------------

it('changing only the procedure keeps the status', function () {
    $appointment = apptTestAppointment();
    $other = Procedure::create(['name' => 'Tooth Extraction', 'price' => 1500]);

    $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'procedure_id' => $other->id,
    ]));

    $appointment->refresh();
    expect($appointment->status)->toBe('scheduled');
    expect($appointment->procedure_id)->toBe($other->id);
});

it('changing the date or time sets the status to rescheduled', function () {
    $appointment = apptTestAppointment();

    $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'appointment_time' => '10:00',
    ]));

    expect($appointment->fresh()->status)->toBe('rescheduled');
});

it('a staff reschedule of an approved appointment stays approved', function () {
    $appointment = apptTestAppointment(['is_approve' => IsApproveEnum::Approve->value]);

    $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'appointment_time' => '10:00',
    ]));

    expect($appointment->fresh()->is_approve)->toBe(IsApproveEnum::Approve->value);
});

it('re-saving with the same date and time does not reschedule', function () {
    $appointment = apptTestAppointment();

    $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'appointment_time' => '09:00',
    ]));

    expect($appointment->fresh()->status)->toBe('scheduled');
});

it('a staff edit cannot move onto a slot another appointment already holds', function () {
    apptTestAppointment(['appointment_time' => '10:00:00']);
    $appointment = apptTestAppointment(['appointment_time' => '09:00:00']);

    $response = $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'appointment_time' => '10:00',
    ]));

    $response->assertSessionHasErrors('appointment_time');
    expect($appointment->fresh()->appointment_time)->toBe('09:00:00');
});

// ---------------------------------------------------------------------------
// editing and rescheduling as a patient
// ---------------------------------------------------------------------------

it('a patient reschedule of a date or time sets rescheduled and waits for clinic confirmation', function () {
    $appointment = apptTestAppointment(['is_approve' => IsApproveEnum::Approve->value]);
    $this->actingAs($this->patient, 'patient');

    $this->put(route('patient.appointments.update', $appointment), [
        'procedure_id' => $this->procedure->id,
        'dentist_id' => $this->dentist->id,
        'appointment_date' => $this->date,
        'appointment_time' => '10:00',
    ]);

    $appointment->refresh();
    expect($appointment->status)->toBe('rescheduled');
    expect($appointment->is_approve)->toBe(IsApproveEnum::PendingReScheduled->value);
});

it('a patient edit that does not move the booking keeps it awaiting confirmation', function () {
    $appointment = apptTestAppointment(['is_approve' => IsApproveEnum::Pending->value]);
    $this->actingAs($this->patient, 'patient');

    $this->put(route('patient.appointments.update', $appointment), [
        'procedure_id' => $this->procedure->id,
        'dentist_id' => $this->dentist->id,
        'appointment_date' => $this->date,
        'appointment_time' => '09:00',
    ]);

    $appointment->refresh();
    expect($appointment->status)->toBe('scheduled');
    expect($appointment->is_approve)->toBe(IsApproveEnum::Pending->value);
});

it('a patient cannot open or change another patient\'s appointment', function () {
    $appointment = apptTestAppointment();
    $other = apptTestPatient('other.patient@example.com');
    $this->actingAs($other, 'patient');

    $this->get(route('patient.appointments.edit', $appointment))->assertNotFound();
    $this->delete(route('patient.appointments.cancel', $appointment));

    expect($appointment->fresh()->status)->toBe('scheduled');
});

// ---------------------------------------------------------------------------
// cancelling
// ---------------------------------------------------------------------------

it('cancelling keeps the row and sets the status to cancelled', function () {
    $appointment = apptTestAppointment();

    $this->delete(route('appointments.destroy', $appointment));

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled']);
});

it('a patient cancelling a pending request removes it from the approval queue', function () {
    $appointment = apptTestAppointment(['is_approve' => IsApproveEnum::Pending->value]);
    $this->actingAs($this->patient, 'patient');

    $this->delete(route('patient.appointments.cancel', $appointment));

    $appointment->refresh();
    expect($appointment->status)->toBe('cancelled');
    expect($appointment->is_approve)->toBe(IsApproveEnum::Reject->value);
});

// ---------------------------------------------------------------------------
// treatment locks the appointment
// ---------------------------------------------------------------------------

it('an appointment with a treatment cannot be edited or cancelled', function () {
    $appointment = apptTestAppointment();
    Treatment::create([
        'appointment_id' => $appointment->id,
        'user_id' => $this->dentist->id,
        'duration_minutes' => 30,
        'summary' => 'Done',
        'follow_up_schedule' => now()->addWeek()->format('Y-m-d'),
    ]);

    $this->put(route('appointments.update', $appointment), apptTestStaffPayload([
        'appointment_time' => '10:00',
    ]));
    $this->delete(route('appointments.destroy', $appointment));

    $appointment->refresh();
    expect($appointment->appointment_time)->toBe('09:00:00');
    expect($appointment->status)->not->toBe('cancelled');
});

// ---------------------------------------------------------------------------
// the questionnaire is required for every booking
// ---------------------------------------------------------------------------

it('sends a patient to the questionnaire when they open create without answering it', function () {
    $this->actingAs($this->patient, 'patient');

    $response = $this->get(route('patient.appointments.create'));

    $response->assertRedirect(route('patient.appointments.questionnaire'));
});

it('does not book for a patient without a questionnaire in the session, and creates nothing', function () {
    $this->actingAs($this->patient, 'patient');

    $response = $this->post(route('patient.appointments.store'), [
        'procedure_id' => $this->procedure->id,
        'dentist_id' => $this->dentist->id,
        'appointment_date' => $this->date,
        'appointment_time' => '09:00',
    ]);

    $response->assertRedirect(route('patient.appointments.questionnaire'));
    $this->assertDatabaseCount('appointments', 0);
});

it('needs a new questionnaire for each booking, so a second patient booking is refused', function () {
    $this->actingAs($this->patient, 'patient');

    $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('patient.appointments.store'), [
            'procedure_id' => $this->procedure->id,
            'dentist_id' => $this->dentist->id,
            'appointment_date' => $this->date,
            'appointment_time' => '09:00',
        ]);

    $response = $this->post(route('patient.appointments.store'), [
        'procedure_id' => $this->procedure->id,
        'dentist_id' => $this->dentist->id,
        'appointment_date' => $this->date,
        'appointment_time' => '10:00',
    ]);

    $response->assertRedirect(route('patient.appointments.questionnaire'));
    $this->assertDatabaseCount('appointments', 1);
});

it('needs a new questionnaire for each booking, so a second staff booking is refused', function () {
    $this->withSession(['pending_questionnaire' => apptTestPending($this->patient)])
        ->post(route('appointments.store'), apptTestStaffPayload());

    $response = $this->post(route('appointments.store'), apptTestStaffPayload([
        'appointment_time' => '10:00',
    ]));

    $response->assertRedirect(route('appointments.questionnaire'));
    $this->assertDatabaseCount('appointments', 1);
});

it('rejects a patient questionnaire that leaves a general question unanswered', function () {
    $this->actingAs($this->patient, 'patient');

    $sections = MedicalQuestionnaire::sections();
    $keys = collect($sections['general'])->pluck('key')->all();
    $answers = collect($keys)->slice(1)->mapWithKeys(fn ($k) => [$k => false])->all(); // first question missing

    $response = $this->post(route('patient.appointments.questionnaire.save'), [
        'answers' => $answers,
        'conditions' => [],
        'agreed' => true,
    ]);

    $response->assertSessionHasErrors('answers.' . $keys[0]);
});
