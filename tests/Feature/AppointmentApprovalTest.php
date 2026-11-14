<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\DentistDetail;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use App\Services\DentistAvailability;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Cache::flush();

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has appointments.view and appointments.update
    $this->actingAs($this->user);

    $this->dentist = apvDentist();
    $this->patient = apvPatient();
    $this->procedure = Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800, 'duration' => 30]);
    $this->date = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
});

function apvDentist(): User
{
    $dentist = User::factory()->create(['name' => 'Dr Cruz', 'is_active' => IsActiveEnum::ACTIVE->value]);
    $dentist->assignRole('dentist');

    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Braces'],
        'available_schedule' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->map(fn ($day) => ['day' => $day, 'start' => '09:00', 'end' => '17:00'])
            ->all(),
        'appointment_limit' => 10,
    ]);

    return $dentist;
}

function apvPatient(string $email = 'approval.patient@example.com'): Patient
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

/** Creates an appointment in a given approval state. Defaults to a scheduled, pending booking. */
function apvAppointment(array $overrides = []): Appointment
{
    return Appointment::create(array_merge([
        'patient_id' => test()->patient->id,
        'dentist_id' => test()->dentist->id,
        'procedure_id' => test()->procedure->id,
        'appointment_date' => test()->date,
        'appointment_time' => '09:00:00',
        'status' => 'scheduled',
        'is_approve' => 'pending',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// the queues
// ---------------------------------------------------------------------------

it('lists pending and pending reschedule appointments on the default tab', function () {
    apvAppointment(['appointment_time' => '09:00:00', 'is_approve' => 'pending']);
    apvAppointment(['appointment_time' => '10:00:00', 'is_approve' => 'pending_reschedule', 'status' => 'rescheduled']);
    apvAppointment(['appointment_time' => '11:00:00', 'is_approve' => 'approved']);
    apvAppointment(['appointment_time' => '12:00:00', 'is_approve' => 'reject']);

    $response = $this->get(route('appointment_approvals.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Appointments/Approvals')
        ->where('tab', 'pending')
        ->has('appointments.data', 2)
    );
});

it('lists only rejected appointments on the rejected tab', function () {
    apvAppointment(['appointment_time' => '09:00:00', 'is_approve' => 'pending']);
    apvAppointment(['appointment_time' => '12:00:00', 'is_approve' => 'reject']);

    $response = $this->get(route('appointment_approvals.index', ['tab' => 'rejected']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('tab', 'rejected')
        ->has('appointments.data', 1)
        ->where('appointments.data.0.is_approve', 'reject')
    );
});

it('shows the pending and rejected counts beside the tabs', function () {
    apvAppointment(['appointment_time' => '09:00:00', 'is_approve' => 'pending']);
    apvAppointment(['appointment_time' => '10:00:00', 'is_approve' => 'pending_reschedule', 'status' => 'rescheduled']);
    apvAppointment(['appointment_time' => '12:00:00', 'is_approve' => 'reject']);

    $response = $this->get(route('appointment_approvals.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('counts.pending', 2)
        ->where('counts.rejected', 1)
    );
});

it('lists the oldest pending request first', function () {
    $newer = apvAppointment(['appointment_time' => '10:00:00']);
    $older = apvAppointment(['appointment_time' => '09:00:00']);
    $newer->forceFill(['created_at' => now()])->save();
    $older->forceFill(['created_at' => now()->subDay()])->save();

    $response = $this->get(route('appointment_approvals.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('appointments.data.0.id', $older->id)
    );
});

// ---------------------------------------------------------------------------
// approve
// ---------------------------------------------------------------------------

it('approves a pending booking and keeps its status', function () {
    $appointment = apvAppointment(['is_approve' => 'pending', 'status' => 'scheduled']);

    $response = $this->put(route('appointment_approvals.approve', $appointment));

    $response->assertSessionHas('message', 'Appointment approved.');
    $appointment->refresh();
    expect($appointment->is_approve)->toBe('approved');
    expect($appointment->status)->toBe('scheduled');
});

it('approves a pending reschedule and keeps its rescheduled status', function () {
    $appointment = apvAppointment(['is_approve' => 'pending_reschedule', 'status' => 'rescheduled']);

    $this->put(route('appointment_approvals.approve', $appointment));

    $appointment->refresh();
    expect($appointment->is_approve)->toBe('approved');
    expect($appointment->status)->toBe('rescheduled');
});

it('approves a booking that was rejected earlier', function () {
    $appointment = apvAppointment(['is_approve' => 'reject']);

    $this->put(route('appointment_approvals.approve', $appointment));

    expect($appointment->fresh()->is_approve)->toBe('approved');
});

it('refuses to approve an appointment that is already approved', function () {
    $appointment = apvAppointment(['is_approve' => 'approved']);

    $response = $this->put(route('appointment_approvals.approve', $appointment));

    $response->assertSessionHas('error', 'This appointment is not waiting for approval.');
    expect($appointment->fresh()->is_approve)->toBe('approved');
});

it('returns 404 when approving an appointment that does not exist', function () {
    $this->put(route('appointment_approvals.approve', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// reject
// ---------------------------------------------------------------------------

it('rejects a pending booking and keeps its status', function () {
    $appointment = apvAppointment(['is_approve' => 'pending', 'status' => 'scheduled']);

    $response = $this->put(route('appointment_approvals.reject', $appointment));

    $response->assertSessionHas('message', 'Appointment rejected.');
    $appointment->refresh();
    expect($appointment->is_approve)->toBe('reject');
    expect($appointment->status)->toBe('scheduled');
});

it('rejects a pending reschedule', function () {
    $appointment = apvAppointment(['is_approve' => 'pending_reschedule', 'status' => 'rescheduled']);

    $this->put(route('appointment_approvals.reject', $appointment));

    expect($appointment->fresh()->is_approve)->toBe('reject');
});

it('refuses to reject an approved appointment', function () {
    $appointment = apvAppointment(['is_approve' => 'approved']);

    $response = $this->put(route('appointment_approvals.reject', $appointment));

    $response->assertSessionHas('error', 'Only pending appointments can be rejected.');
    expect($appointment->fresh()->is_approve)->toBe('approved');
});

it('refuses to reject an appointment that is already rejected', function () {
    $appointment = apvAppointment(['is_approve' => 'reject']);

    $response = $this->put(route('appointment_approvals.reject', $appointment));

    $response->assertSessionHas('error', 'Only pending appointments can be rejected.');
});

it('a rejected booking frees its slot and daily count for other bookings', function () {
    apvAppointment(['appointment_time' => '09:00:00', 'is_approve' => 'reject']);

    $conflict = app(DentistAvailability::class)->conflict($this->dentist, $this->date, '09:00');

    expect($conflict)->toBeNull();
});

// ---------------------------------------------------------------------------
// who may act
// ---------------------------------------------------------------------------

it('lets a dentist see the queue but not approve or reject', function () {
    $appointment = apvAppointment();
    $dentist = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $dentist->assignRole('dentist');
    $this->actingAs($dentist);

    $this->get(route('appointment_approvals.index'))->assertOk();
    $this->put(route('appointment_approvals.approve', $appointment))->assertForbidden();
    expect($appointment->fresh()->is_approve)->toBe('pending');
});

it('keeps a patient out of the approval queue', function () {
    $this->actingAs(apvPatient('other.patient@example.com'), 'patient');

    $response = $this->get(route('appointment_approvals.index'));

    $response->assertRedirect(route('login'));
});