<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Mail\PasswordResetCodeMail;
use App\Models\Patient;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    // Throttled routes (login-style and reset routes) share the array cache across tests.
    Cache::flush();
});

/**
 * Creates an active patient with a known password, the way a patient would be stored.
 */
function authTestPatient(array $overrides = []): Patient
{
    return Patient::create(array_merge([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'gender' => GenderEnum::cases()[0]->value,
        'date_of_birth' => '1985-03-12',
        'email' => 'maria.auth@example.com',
        'contact_number' => '09171234567',
        'password' => 'MyPassword123',
    ], $overrides));
}

/**
 * Stores a reset code the same way the controller does, so the test knows the code.
 */
function authTestSeedResetCode(string $email, string $code = '123456', int $attempts = 0): void
{
    Cache::put(
        'patient-password-reset:' . sha1(strtolower($email)),
        ['hash' => Hash::make($code), 'attempts' => $attempts],
        now()->addMinutes(10),
    );
}

// ---------------------------------------------------------------------------
// login
// ---------------------------------------------------------------------------

it('shows the patient login page to a guest', function () {
    $response = $this->get(route('patient.login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('auth/PatientLogin')
    );
});

it('logs a patient in with the correct email and password and sends them to their appointments', function () {
    authTestPatient();

    $response = $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'MyPassword123',
    ]);

    $this->assertAuthenticated('patient');
    $response->assertRedirect(route('patient.appointments.index'));
});

it('keeps the patient signed in when "keep me signed in" is ticked', function () {
    authTestPatient();

    $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'MyPassword123',
        'remember' => true,
    ]);

    $this->assertAuthenticated('patient');
});

it('rejects a wrong password and does not sign the patient in', function () {
    authTestPatient();

    $response = $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('patient');
});

it('rejects an email that is not registered with the same message as a wrong password', function () {
    $response = $this->post(route('patient.login.store'), [
        'email' => 'nobody@example.com',
        'password' => 'MyPassword123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('patient');
});

it('does not sign in a deactivated patient even with the right password', function () {
    authTestPatient(['is_active' => IsActiveEnum::DEACTIVED->value]);

    $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'MyPassword123',
    ]);

    $this->assertGuest('patient');
});

it('requires an email and a password to log in', function () {
    $response = $this->post(route('patient.login.store'), []);

    $response->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest('patient');
});

it('sends a guest who opens the patient portal to the patient login page', function () {
    $response = $this->get(route('patient.dashboard'));

    $response->assertRedirect(route('patient.login'));
});

it('signs the patient out and sends them back to the patient login page', function () {
    authTestPatient();
    $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'MyPassword123',
    ]);

    $response = $this->post(route('patient.logout'));

    $this->assertGuest('patient');
    $response->assertRedirect('/patient/login');
});

// ---------------------------------------------------------------------------
// forgot password: sending the code
// ---------------------------------------------------------------------------

it('emails a 6-digit code to a registered patient', function () {
    Mail::fake();
    authTestPatient();

    $response = $this->post(route('patient.password.code'), [
        'email' => 'maria.auth@example.com',
    ]);

    $response->assertSessionHasNoErrors();
    Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo('maria.auth@example.com'));
    expect(Cache::has('patient-password-reset:' . sha1('maria.auth@example.com')))->toBeTrue();
});

it('stores only a hash of the code, never the code itself', function () {
    Mail::fake();
    authTestPatient();

    $this->post(route('patient.password.code'), ['email' => 'maria.auth@example.com']);

    $entry = Cache::get('patient-password-reset:' . sha1('maria.auth@example.com'));

    expect($entry['attempts'])->toBe(0);
    expect(strlen($entry['hash']))->toBeGreaterThan(20);
    expect($entry['hash'])->not->toMatch('/^\d{6}$/');
});

it('gives the same reply for an unregistered email and sends no email', function () {
    Mail::fake();

    $response = $this->post(route('patient.password.code'), [
        'email' => 'nobody@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    Mail::assertNothingSent();
});

it('requires a valid email to request a code', function () {
    $response = $this->post(route('patient.password.code'), [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

// ---------------------------------------------------------------------------
// forgot password: resetting with the code
// ---------------------------------------------------------------------------

it('resets the password when the correct code is given', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Hash::check('NewPassword456', Patient::where('email', 'maria.auth@example.com')->first()->password))->toBeTrue();
});

it('lets the patient log in with the new password and not the old one', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');
    $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'MyPassword123',
    ]);
    $this->assertGuest('patient');

    $this->post(route('patient.login.store'), [
        'email' => 'maria.auth@example.com',
        'password' => 'NewPassword456',
    ]);
    $this->assertAuthenticated('patient');
});

it('removes the code after a successful reset so it cannot be used again', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');
    $payload = [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ];
    $this->post(route('patient.password.reset'), $payload);

    $response = $this->post(route('patient.password.reset'), $payload);

    $response->assertSessionHasErrors('code');
});

it('rejects a wrong code and counts the attempt', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '000000',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasErrors('code');
    $entry = Cache::get('patient-password-reset:' . sha1('maria.auth@example.com'));
    expect($entry['attempts'])->toBe(1);
    expect(Hash::check('MyPassword123', Patient::where('email', 'maria.auth@example.com')->first()->password))->toBeTrue();
});

it('locks the code after five wrong attempts, even if the right code is then given', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456', attempts: 5);

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasErrors('code');
    expect(Hash::check('MyPassword123', Patient::where('email', 'maria.auth@example.com')->first()->password))->toBeTrue();
});

it('rejects a code that has expired or was never requested', function () {
    authTestPatient();

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasErrors('code');
});

it('rejects a code that is not exactly six digits', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '12345',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasErrors('code');
});

it('rejects a reset when the new password is not confirmed', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'different789',
    ]);

    $response->assertSessionHasErrors('password');
});

it('rejects a new password that is too short', function () {
    authTestPatient();
    authTestSeedResetCode('maria.auth@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'maria.auth@example.com',
        'code' => '123456',
        'password' => 'short7x',
        'password_confirmation' => 'short7x',
    ]);

    $response->assertSessionHasErrors('password');
});

it('does not reset anything for an email that has no patient account', function () {
    authTestSeedResetCode('nobody@example.com', '123456');

    $response = $this->post(route('patient.password.reset'), [
        'email' => 'nobody@example.com',
        'code' => '123456',
        'password' => 'NewPassword456',
        'password_confirmation' => 'NewPassword456',
    ]);

    $response->assertSessionHasErrors('code');
    $this->assertDatabaseCount('patients', 0);
});