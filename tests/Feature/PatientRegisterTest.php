<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Patient;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class); // creates the "patient" role on the patient guard

    // The register route is throttled (5 per minute). The array cache persists across
    // tests in one run, so clear it or later tests get 429 responses.
    Cache::flush();
});

/**
 * A valid registration payload, shaped exactly like the registration form sends it.
 * Empty optional fields are sent as empty strings, the same as the browser does.
 */
function registerTestPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Maria',
        'middle_name' => '',
        'last_name' => 'Santos',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1985-03-12',
        'email' => 'maria.register@example.com',
        'contact_number' => '09171234567',
        'address' => '',
        'emergency_contact_name' => '',
        'emergency_contact_number' => '',
        'medical_history' => '',
        'allergies' => '',
        'password' => 'MyPassword123',
        'password_confirmation' => 'MyPassword123',
    ], $overrides);
}

dataset('invalidRegisterData', [
    'first_name missing' => [['first_name' => ''], 'first_name'],
    'first_name too short' => [['first_name' => 'a'], 'first_name'],
    'last_name missing' => [['last_name' => ''], 'last_name'],
    'gender invalid' => [['gender' => 'not-a-gender'], 'gender'],
    'date_of_birth missing' => [['date_of_birth' => ''], 'date_of_birth'],
    'date_of_birth today' => [['date_of_birth' => now()->format('Y-m-d')], 'date_of_birth'],
    'date_of_birth in future' => [['date_of_birth' => now()->addYear()->format('Y-m-d')], 'date_of_birth'],
    'email missing' => [['email' => ''], 'email'],
    'email not an address' => [['email' => 'not-an-email'], 'email'],
    'contact_number missing' => [['contact_number' => ''], 'contact_number'],
    'contact_number too short' => [['contact_number' => '0917123'], 'contact_number'],
    'password missing' => [['password' => '', 'password_confirmation' => ''], 'password'],
    'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password not confirmed' => [['password_confirmation' => 'different123'], 'password'],
    'medical_history too long' => [['medical_history' => str_repeat('a', 301)], 'medical_history'],
    'allergies too long' => [['allergies' => str_repeat('a', 301)], 'allergies'],
]);

// ---------------------------------------------------------------------------
// the registration page
// ---------------------------------------------------------------------------

it('shows the registration page to a guest', function () {
    $response = $this->get(route('patient.register'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('auth/PatientRegister')
    );
});

it('sends a signed-in patient away from the registration page', function () {
    $this->post(route('patient.register.store'), registerTestPayload())->assertSessionHasNoErrors();
    $patient = Patient::where('email', 'maria.register@example.com')->firstOrFail();

    $this->actingAs($patient, 'patient');

    $response = $this->get(route('patient.register'));

    $response->assertRedirect(route('patient.dashboard'));
});

// ---------------------------------------------------------------------------
// creating the account
// ---------------------------------------------------------------------------

it('creates a patient account with valid data and redirects with a welcome message', function () {
    $response = $this->post(route('patient.register.store'), registerTestPayload());

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('patient.appointments.index'));
    $response->assertSessionHas('message', 'Welcome to Klinika! Your account has been created.');

    $this->assertDatabaseHas('patients', [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.register@example.com',
        'contact_number' => '09171234567',
    ]);
});

it('stores the password the patient chose, hashed, not the contact number', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $patient = Patient::where('email', 'maria.register@example.com')->firstOrFail();

    expect(Hash::check('MyPassword123', $patient->password))->toBeTrue();
    expect(Hash::check('09171234567', $patient->password))->toBeFalse();
    expect($patient->password)->not->toBe('MyPassword123');
});

it('creates the new patient as active with the patient role', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $patient = Patient::where('email', 'maria.register@example.com')->firstOrFail();

    expect($patient->is_active)->toBe(IsActiveEnum::ACTIVE);
    expect($patient->hasRole('patient'))->toBeTrue();
});

it('ignores an is_active value sent in the request', function () {
    $this->post(route('patient.register.store'), registerTestPayload([
        'is_active' => IsActiveEnum::DEACTIVED->value,
    ]));

    $patient = Patient::where('email', 'maria.register@example.com')->firstOrFail();

    expect($patient->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('saves the full profile the form sends', function () {
    $this->post(route('patient.register.store'), registerTestPayload([
        'middle_name' => 'Lopez',
        'address' => 'Naga City, Camarines Sur',
        'medical_history' => 'Hypertension',
        'allergies' => 'Penicillin',
        'emergency_contact_name' => 'Juan Santos',
        'emergency_contact_number' => '09181234567',
    ]));

    $this->assertDatabaseHas('patients', [
        'email' => 'maria.register@example.com',
        'middle_name' => 'Lopez',
        'address' => 'Naga City, Camarines Sur',
        'medical_history' => 'Hypertension',
        'allergies' => 'Penicillin',
        'emergency_contact_name' => 'Juan Santos',
        'emergency_contact_number' => '09181234567',
    ]);
});

it('stores blank optional fields as null', function () {
    $this->post(route('patient.register.store'), registerTestPayload([
        'middle_name' => '',
        'address' => '',
        'medical_history' => '',
        'allergies' => '',
        'emergency_contact_name' => '',
        'emergency_contact_number' => '',
    ]));

    $this->assertDatabaseHas('patients', [
        'email' => 'maria.register@example.com',
        'middle_name' => null,
        'address' => null,
        'medical_history' => null,
        'allergies' => null,
        'emergency_contact_name' => null,
        'emergency_contact_number' => null,
    ]);
});

it('rejects a duplicate email and writes no second account', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $response = $this->post(route('patient.register.store'), registerTestPayload([
        'first_name' => 'Pedro',
        'contact_number' => '09991234567',
    ]));

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('patients', 1);
});

it('rejects an emergency contact name without a number, as the form asks for both', function () {
    $response = $this->post(route('patient.register.store'), registerTestPayload([
        'emergency_contact_name' => 'Juan Santos',
        'emergency_contact_number' => '',
    ]));

    $response->assertSessionHasErrors('emergency_contact_number');
    $this->assertDatabaseCount('patients', 0);
});

it('rejects registration with invalid data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $response = $this->post(route('patient.register.store'), registerTestPayload($overrides));

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('patients', 0);
})->with('invalidRegisterData');

// ---------------------------------------------------------------------------
// the new patient can sign in
// ---------------------------------------------------------------------------

it('keeps the new patient signed in straight after registering', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $this->assertAuthenticated('patient');
});

it('lets the new patient open the portal straight after registering', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $response = $this->get(route('patient.dashboard'));

    dump('dashboard redirected to: ' . $response->headers->get('Location'));

    $response->assertOk();
});

it('lets the new patient log out and back in with the password they chose', function () {
    $this->post(route('patient.register.store'), registerTestPayload());

    $this->post(route('patient.logout'));
    $this->assertGuest('patient');

    $this->post(route('patient.login.store'), [
        'email' => 'maria.register@example.com',
        'password' => 'MyPassword123',
    ]);

    $this->assertAuthenticated('patient');
});

it('does not let the new patient sign in with the contact number as the password', function () {
    $this->post(route('patient.register.store'), registerTestPayload());
    $this->post(route('patient.logout'));

    $this->post(route('patient.login.store'), [
        'email' => 'maria.register@example.com',
        'password' => '09171234567',
    ]);

    $this->assertGuest('patient');
});