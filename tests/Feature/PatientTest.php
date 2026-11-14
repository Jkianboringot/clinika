<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has patients.view/create/update/delete

    $this->actingAs($this->user);
});

/**
 * A valid staff-side create payload. Override fields as needed.
 */
function patientTestPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1950-05-20',
        'contact_number' => '09171234567',
        'email' => 'juan.patient@example.com',
        'address' => 'Naga City, Camarines Sur',
        'medical_history' => 'Hypertension',
        'allergies' => 'Penicillin',
        'emergency_contact_name' => 'Maria Cruz',
        'emergency_contact_number' => '09181234567',
    ], $overrides);
}

/**
 * Creates a patient through the real staff store route and returns the saved record.
 */
function patientTestCreate(array $overrides = []): Patient
{
    $data = patientTestPayload($overrides);

    test()->post(route('patients.store'), $data)->assertSessionHasNoErrors();

    return Patient::where('email', $data['email'])->firstOrFail();
}

/**
 * Logs a patient in through the patient login form.
 */
function patientTestLogin(string $email, string $password)
{
    return test()->post(route('patient.login.store'), [
        'email' => $email,
        'password' => $password,
    ]);
}

dataset('invalidPatientData', [
    'first_name missing' => [['first_name' => ''], 'first_name'],
    'first_name too short' => [['first_name' => 'a'], 'first_name'],
    'last_name missing' => [['last_name' => ''], 'last_name'],
    'last_name too long' => [['last_name' => str_repeat('a', 101)], 'last_name'],
    'gender invalid' => [['gender' => 'not-a-gender'], 'gender'],
    'date_of_birth missing' => [['date_of_birth' => ''], 'date_of_birth'],
    'date_of_birth today' => [['date_of_birth' => now()->format('Y-m-d')], 'date_of_birth'],
    'date_of_birth in future' => [['date_of_birth' => now()->addYear()->format('Y-m-d')], 'date_of_birth'],
    'email missing' => [['email' => ''], 'email'],
    'email not an address' => [['email' => 'not-an-email'], 'email'],
    'contact_number too short' => [['contact_number' => '0917123'], 'contact_number'],
    'emergency_contact_number too short' => [['emergency_contact_number' => '0918'], 'emergency_contact_number'],
    'medical_history too long' => [['medical_history' => str_repeat('a', 301)], 'medical_history'],
]);

// ---------------------------------------------------------------------------
// staff creates a patient
// ---------------------------------------------------------------------------

it('creates a patient with valid data, redirects with a success message and the record is in db', function () {
    $response = $this->post(route('patients.store'), patientTestPayload());

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('patients.index'));
    $response->assertSessionHas('message', 'Patient created successfully.');

    $this->assertDatabaseHas('patients', [
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'email' => 'juan.patient@example.com',
        'contact_number' => '09171234567',
    ]);
});

it('gives the created patient the patient role on the patient guard', function () {
    $patient = patientTestCreate();

    expect($patient->hasRole('patient'))->toBeTrue();
});

it('creates the patient as active', function () {
    $patient = patientTestCreate();

    expect($patient->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('sets the default password to the contact number, stored hashed', function () {
    $patient = patientTestCreate();

    expect(Hash::check('09171234567', $patient->password))->toBeTrue();
    expect($patient->password)->not->toBe('09171234567');
});

it('lets the created patient log in with email and contact number', function () {
    $patient = patientTestCreate();

    $response = patientTestLogin($patient->email, '09171234567');

    $this->assertAuthenticated('patient');
    $response->assertRedirect(route('patient.dashboard'));
});

it('lets the created patient open the patient dashboard after logging in', function () {
    $patient = patientTestCreate();
    patientTestLogin($patient->email, '09171234567');

    $response = $this->get(route('patient.dashboard'));

    $response->assertOk();
});

it('does not log the created patient in with a wrong password', function () {
    $patient = patientTestCreate();

    patientTestLogin($patient->email, 'wrong-password');

    $this->assertGuest('patient');
});

it('stores nullable fields as null when they are left out', function () {
    $response = $this->post(route('patients.store'), [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1960-01-01',
        'contact_number' => '09171234567',
        'email' => 'maria.santos@example.com',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('patients', [
        'email' => 'maria.santos@example.com',
        'middle_name' => null,
        'address' => null,
        'medical_history' => null,
        'allergies' => null,
        'emergency_contact_name' => null,
        'emergency_contact_number' => null,
    ]);
});

it('rejects a missing contact number, because the default password comes from it', function () {
    $response = $this->post(route('patients.store'), patientTestPayload(['contact_number' => null]));

    $response->assertSessionHasErrors('contact_number');
    $this->assertDatabaseCount('patients', 0);
});

it('rejects create with invalid patient data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $response = $this->post(route('patients.store'), patientTestPayload($overrides));

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('patients', 0);
})->with('invalidPatientData');

it('rejects a duplicate email and writes no second patient', function () {
    patientTestCreate();

    $response = $this->post(route('patients.store'), patientTestPayload([
        'first_name' => 'Pedro',
        'contact_number' => '09991234567',
    ]));

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('patients', 1);
});

it('blocks a dentist from creating a patient', function () {
    $dentist = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $dentist->assignRole('dentist');
    $this->actingAs($dentist);

    $response = $this->post(route('patients.store'), patientTestPayload());

    $response->assertForbidden();
    $this->assertDatabaseCount('patients', 0);
});

// ---------------------------------------------------------------------------
// listing and viewing
// ---------------------------------------------------------------------------

it('lists patients on the index page', function () {
    patientTestCreate();

    $response = $this->get(route('patients.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Index')
        ->has('patients.data', 1)
        ->where('patients.data.0.email', 'juan.patient@example.com')
    );
});

it('never sends the password to the list page', function () {
    patientTestCreate();

    $response = $this->get(route('patients.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('patients.data.0.password')
    );
});

it('finds a patient by first name', function () {
    patientTestCreate();
    patientTestCreate(['first_name' => 'Pedro', 'email' => 'pedro@example.com', 'contact_number' => '09991234567']);

    $response = $this->get(route('patients.index', ['search' => 'Juan']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('patients.data', 1)
        ->where('patients.data.0.first_name', 'Juan')
    );
});

it('finds a patient by full name typed as one string', function () {
    patientTestCreate();

    $response = $this->get(route('patients.index', ['search' => 'Juan Dela Cruz']));

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('patients.data', 1));
});

it('shows the edit page without the password', function () {
    $patient = patientTestCreate();

    $response = $this->get(route('patients.edit', $patient));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Edit')
        ->where('patient.id', $patient->id)
        ->where('patient.email', 'juan.patient@example.com')
        ->missing('patient.password')
    );
});

it('shows the patient detail page', function () {
    $patient = patientTestCreate();

    $response = $this->get(route('patients.show', $patient));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Patients/Show')
        ->where('patient.id', $patient->id)
    );
});

it('returns 404 for a patient that does not exist on the edit page', function () {
    $this->get(route('patients.edit', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// updating
// ---------------------------------------------------------------------------

it('updates patient details, redirects with a success message and the record is updated in db', function () {
    $patient = patientTestCreate();

    $response = $this->put(route('patients.update', $patient), patientTestPayload([
        'address' => 'Calabanga, Camarines Sur',
        'medical_history' => 'Diabetes',
    ]));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('patients.index'));
    $response->assertSessionHas('message', 'Patient updated successfully.');

    $this->assertDatabaseHas('patients', [
        'id' => $patient->id,
        'address' => 'Calabanga, Camarines Sur',
        'medical_history' => 'Diabetes',
    ]);
});

it('keeps the patient password and login working after an update', function () {
    $patient = patientTestCreate();

    $this->put(route('patients.update', $patient), patientTestPayload(['address' => 'New address']));

    expect(Hash::check('09171234567', $patient->fresh()->password))->toBeTrue();

    patientTestLogin($patient->email, '09171234567');
    $this->assertAuthenticated('patient');
});

it('allows a patient to keep their own email when updated', function () {
    $patient = patientTestCreate();

    $response = $this->put(route('patients.update', $patient), patientTestPayload());

    $response->assertSessionHasNoErrors();
});

it('rejects an update that uses another patient\'s email', function () {
    patientTestCreate();
    $second = patientTestCreate(['email' => 'pedro@example.com', 'contact_number' => '09991234567']);

    $response = $this->put(route('patients.update', $second), patientTestPayload([
        'email' => 'juan.patient@example.com',
        'contact_number' => '09991234567',
    ]));

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseHas('patients', ['id' => $second->id, 'email' => 'pedro@example.com']);
});

it('rejects update with invalid patient data and leaves the record unchanged', function () {
    $patient = patientTestCreate();

    $response = $this->put(route('patients.update', $patient), patientTestPayload(['first_name' => 'a']));

    $response->assertSessionHasErrors('first_name');
    $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Juan']);
});

it('returns 404 when updating a patient that does not exist', function () {
    $this->put(route('patients.update', 999999), patientTestPayload())->assertNotFound();
});

// ---------------------------------------------------------------------------
// deactivating and reactivating
// ---------------------------------------------------------------------------

it('deactivates a patient but keeps the row in db', function () {
    $patient = patientTestCreate();

    $response = $this->put(route('patients.deactivate', $patient));

    $response->assertRedirect(route('patients.index'));
    $response->assertSessionHas('message', 'Patient deactivated successfully.');
    expect($patient->fresh()->is_active)->toBe(IsActiveEnum::DEACTIVED);
    $this->assertDatabaseHas('patients', ['id' => $patient->id]);
});

it('stops a deactivated patient from logging in', function () {
    $patient = patientTestCreate();
    $this->put(route('patients.deactivate', $patient));

    patientTestLogin($patient->email, '09171234567');

    $this->assertGuest('patient');
});

it('removes the sessions belonging to a deactivated patient', function () {
    $patient = patientTestCreate();
    DB::table('sessions')->insert([
        'id' => 'session-for-patient',
        'user_id' => $patient->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => 'payload',
        'last_activity' => time(),
    ]);

    $this->put(route('patients.deactivate', $patient));

    $this->assertDatabaseMissing('sessions', ['user_id' => $patient->id]);
});

it('reactivates a deactivated patient, who can then log in again', function () {
    $patient = patientTestCreate();
    $this->put(route('patients.deactivate', $patient));

    $response = $this->put(route('patients.activate', $patient));

    $response->assertRedirect(route('patients.index'));
    $response->assertSessionHas('message', 'Patient activated successfully.');
    expect($patient->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);

    patientTestLogin($patient->email, '09171234567');
    $this->assertAuthenticated('patient');
});

it('does not reactivate a patient who is already active', function () {
    $patient = patientTestCreate();

    $response = $this->put(route('patients.activate', $patient));

    $response->assertSessionHas('error', 'Only deactivated patients can be activated.');
    expect($patient->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('can deactivate and reactivate a patient as many times as needed', function () {
    $patient = patientTestCreate();

    $this->put(route('patients.deactivate', $patient));
    $this->put(route('patients.activate', $patient));
    $this->put(route('patients.deactivate', $patient));
    $this->put(route('patients.activate', $patient));

    expect($patient->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('returns 404 when deactivating a patient that does not exist', function () {
    $this->put(route('patients.deactivate', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// self-registration
// ---------------------------------------------------------------------------

it('lets a patient register themselves and log in with the password they chose', function () {
    $response = $this->post(route('patient.register.store'), patientTestPayload([
        'email' => 'self.register@example.com',
        'password' => 'mypassword123',
        'password_confirmation' => 'mypassword123',
    ]));

    $response->assertSessionHasNoErrors();

    $patient = Patient::where('email', 'self.register@example.com')->firstOrFail();
    expect($patient->hasRole('patient'))->toBeTrue();

    patientTestLogin('self.register@example.com', 'mypassword123');
    $this->assertAuthenticated('patient');
});