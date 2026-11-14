<?php

use App\Enums\IsActiveEnum;
use App\Models\DentistDetail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    // Only admins create, edit, and deactivate accounts, so the default test user is an admin.
    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('admin');

    $this->actingAs($this->user);
});

/**
 * Creates an active user with a role. Used for setting up other accounts.
 */
function userTestWithRole(string $role, array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'is_active' => IsActiveEnum::ACTIVE->value,
    ], $overrides));
    $user->assignRole($role);

    return $user;
}

/**
 * A valid create or update payload for a dentist. Override fields as needed.
 */
function userTestDentistPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dr Maria Santos',
        'email' => 'maria.santos@example.com',
        'role' => 'dentist',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'dentist' => [
            'specialization' => 'Orthodontics, Braces',
            'appointment_limit' => 10,
            'schedule' => [
                ['day' => 'monday', 'start' => '09:00', 'end' => '17:00'],
            ],
        ],
    ], $overrides);
}

dataset('invalidUserData', [
    'name too short' => [['name' => 'ab'], 'name'],
    'name too long' => [['name' => str_repeat('a', 76)], 'name'],
    'email missing' => [['email' => ''], 'email'],
    'email too long' => [['email' => str_repeat('a', 141).'@example.com'], 'email'],
    'role missing' => [['role' => ''], 'role'],
    'role not assignable' => [['role' => 'patient'], 'role'],
    'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password not confirmed' => [['password_confirmation' => 'different123'], 'password'],
    'dentist specialization missing' => [['dentist' => ['appointment_limit' => 10, 'schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']]]], 'dentist.specialization'],
    'dentist appointment limit zero' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 0, 'schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']]]], 'dentist.appointment_limit'],
    'dentist appointment limit over maximum' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 51, 'schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']]]], 'dentist.appointment_limit'],
    'dentist schedule empty' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 10, 'schedule' => []]], 'dentist.schedule'],
    'dentist schedule invalid day' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 10, 'schedule' => [['day' => 'funday', 'start' => '09:00', 'end' => '17:00']]]], 'dentist.schedule.0.day'],
    'dentist schedule end before start' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 10, 'schedule' => [['day' => 'monday', 'start' => '17:00', 'end' => '09:00']]]], 'dentist.schedule.0.end'],
    'dentist schedule bad time format' => [['dentist' => ['specialization' => 'Braces', 'appointment_limit' => 10, 'schedule' => [['day' => 'monday', 'start' => '9am', 'end' => '17:00']]]], 'dentist.schedule.0.start'],
]);

// ---------------------------------------------------------------------------
// listing
// ---------------------------------------------------------------------------

it('defaults the user list to the dentist tab', function () {
    $response = $this->get(route('users.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Users/Index')
        ->where('activeRole', 'dentist')
    );
});

it('shows only users with the chosen role on each tab', function () {
    userTestWithRole('dentist', ['name' => 'Dr Maria Santos']);
    userTestWithRole('staff', ['name' => 'Juan Staff']);

    $dentists = $this->get(route('users.index', ['role' => 'dentist']));
    $dentists->assertInertia(fn (AssertableInertia $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Dr Maria Santos')
    );

    $staff = $this->get(route('users.index', ['role' => 'staff']));
    $staff->assertInertia(fn (AssertableInertia $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Juan Staff')
    );
});

it('shows the saved specialization, limit and schedule for dentists', function () {
    $dentist = userTestWithRole('dentist', ['name' => 'Dr Maria Santos']);
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics', 'Braces'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);

    $response = $this->get(route('users.index', ['role' => 'dentist']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('users.data.0.dentist_detail.specialization.0', 'Orthodontics')
        ->where('users.data.0.dentist_detail.appointment_limit', 10)
    );
});

it('searches users by name within the chosen role', function () {
    userTestWithRole('dentist', ['name' => 'Dr Maria Santos']);
    userTestWithRole('dentist', ['name' => 'Dr Pedro Cruz']);

    $response = $this->get(route('users.index', ['role' => 'dentist', 'search' => 'Santos']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Dr Maria Santos')
    );
});

it('still lists deactivated users so they can be reactivated', function () {
    $dentist = userTestWithRole('dentist', ['name' => 'Dr Maria Santos']);
    $dentist->update(['is_active' => IsActiveEnum::DEACTIVED->value]);

    $response = $this->get(route('users.index', ['role' => 'dentist']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.is_active', IsActiveEnum::DEACTIVED->value)
    );
});

it('rejects an unknown role filter', function () {
    $this->get(route('users.index', ['role' => 'not-a-role']))->assertForbidden();
});

// ---------------------------------------------------------------------------
// creating (admin only)
// ---------------------------------------------------------------------------

it('creates a dentist with a hashed password, active status and dentist details', function () {
    $response = $this->post(route('users.store'), userTestDentistPayload());

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('message', 'User created successfully.');

    $created = User::where('email', 'maria.santos@example.com')->firstOrFail();

    expect($created->hasRole('dentist'))->toBeTrue();
    expect($created->is_active)->toBe(IsActiveEnum::ACTIVE);
    expect(Hash::check('secret123', $created->password))->toBeTrue();

    $this->assertDatabaseHas('dentist_details', [
        'user_id' => $created->id,
        'appointment_limit' => 10,
    ]);

    $details = DentistDetail::where('user_id', $created->id)->firstOrFail();
    expect($details->specialization)->toBe(['Orthodontics', 'Braces']);
    expect($details->available_schedule)->toBe([['day' => 'monday', 'start' => '09:00', 'end' => '17:00']]);
});

it('creates a staff account without any dentist details', function () {
    $response = $this->post(route('users.store'), userTestDentistPayload([
        'name' => 'Juan Staff',
        'email' => 'juan.staff@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]));

    $response->assertSessionHasNoErrors();

    $staff = User::where('email', 'juan.staff@example.com')->firstOrFail();
    expect($staff->hasRole('staff'))->toBeTrue();
    $this->assertDatabaseMissing('dentist_details', ['user_id' => $staff->id]);
});

it('ignores dentist details sent with a staff role', function () {
    $this->post(route('users.store'), userTestDentistPayload([
        'email' => 'juan.staff@example.com',
        'role' => 'staff',
    ]));

    $staff = User::where('email', 'juan.staff@example.com')->firstOrFail();
    $this->assertDatabaseMissing('dentist_details', ['user_id' => $staff->id]);
});

it('does not allow creating another admin account', function () {
    $response = $this->post(route('users.store'), userTestDentistPayload([
        'email' => 'second.admin@example.com',
        'role' => 'admin',
        'dentist' => null,
    ]));

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'second.admin@example.com']);
});

it('rejects a duplicate email on create and writes no second user', function () {
    userTestWithRole('staff', ['email' => 'taken@example.com']);

    $response = $this->post(route('users.store'), userTestDentistPayload(['email' => 'taken@example.com']));

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('users', 2); // the admin and the existing staff
});

it('accepts the name boundaries 3 and 75 characters', function (int $length) {
    $response = $this->post(route('users.store'), userTestDentistPayload([
        'name' => str_repeat('a', $length),
        'email' => "name{$length}@example.com",
    ]));

    $response->assertSessionHasNoErrors();
})->with([3, 75]);

it('accepts the password boundary of exactly 8 characters', function () {
    $response = $this->post(route('users.store'), userTestDentistPayload([
        'email' => 'eight@example.com',
        'password' => 'abcdefgh',
        'password_confirmation' => 'abcdefgh',
    ]));

    $response->assertSessionHasNoErrors();
});

it('requires a password when creating an account', function () {
    $response = $this->post(route('users.store'), userTestDentistPayload([
        'password' => null,
        'password_confirmation' => null,
    ]));

    $response->assertSessionHasErrors('password');
});

it('rejects create with invalid user data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $response = $this->post(route('users.store'), userTestDentistPayload($overrides));

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseMissing('users', ['email' => 'maria.santos@example.com']);
})->with('invalidUserData');

it('blocks a staff user from creating accounts', function () {
    $staff = userTestWithRole('staff');
    $this->actingAs($staff);

    $response = $this->post(route('users.store'), userTestDentistPayload());

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'maria.santos@example.com']);
});

// ---------------------------------------------------------------------------
// editing
// ---------------------------------------------------------------------------

it('shows the edit page with the role and dentist details', function () {
    $dentist = userTestWithRole('dentist', ['name' => 'Dr Maria Santos']);
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);

    $response = $this->get(route('users.edit', $dentist));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Users/Edit')
        ->where('user.id', $dentist->id)
        ->where('user.role', 'dentist')
        ->where('user.dentist.appointment_limit', 10)
    );
});

it('returns 404 when opening the edit page for a user that does not exist', function () {
    $this->get(route('users.edit', 999999))->assertNotFound();
});

it('updates name and email and keeps the role', function () {
    $staff = userTestWithRole('staff', ['name' => 'Juan Staff', 'email' => 'juan@example.com']);

    $response = $this->put(route('users.update', $staff), [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('message', 'User updated successfully.');
    $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Juan Dela Cruz']);
});

it('replaces the dentist specialization and schedule on update', function () {
    $dentist = userTestWithRole('dentist', ['email' => 'maria.santos@example.com']);
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);

    $this->put(route('users.update', $dentist), userTestDentistPayload([
        'email' => 'maria.santos@example.com',
        'password' => null,
        'password_confirmation' => null,
        'dentist' => [
            'specialization' => 'Braces, Extraction',
            'appointment_limit' => 20,
            'schedule' => [['day' => 'friday', 'start' => '13:00', 'end' => '18:00']],
        ],
    ]));

    $details = DentistDetail::where('user_id', $dentist->id)->firstOrFail();
    expect($details->specialization)->toBe(['Braces', 'Extraction']);
    expect($details->appointment_limit)->toBe(20);
    expect($details->available_schedule)->toBe([['day' => 'friday', 'start' => '13:00', 'end' => '18:00']]);
});

it('removes the dentist details when a dentist is changed to staff', function () {
    $dentist = userTestWithRole('dentist', ['email' => 'maria.santos@example.com']);
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);

    $this->put(route('users.update', $dentist), [
        'name' => 'Dr Maria Santos',
        'email' => 'maria.santos@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    expect($dentist->fresh()->hasRole('staff'))->toBeTrue();
    $this->assertDatabaseMissing('dentist_details', ['user_id' => $dentist->id]);
});

it('keeps the current email when updating the same user', function () {
    $staff = userTestWithRole('staff', ['email' => 'juan@example.com']);

    $response = $this->put(route('users.update', $staff), [
        'name' => 'Juan Staff',
        'email' => 'juan@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    $response->assertSessionHasNoErrors();
});

it('rejects changing a user to an email already used by someone else', function () {
    userTestWithRole('staff', ['email' => 'taken@example.com']);
    $staff = userTestWithRole('staff', ['email' => 'juan@example.com']);

    $response = $this->put(route('users.update', $staff), [
        'name' => 'Juan Staff',
        'email' => 'taken@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    $response->assertSessionHasErrors('email');
});

it('blocks editing your own account from this page', function () {
    $response = $this->put(route('users.update', $this->user), [
        'name' => 'Changed Name',
        'email' => $this->user->email,
        'role' => 'admin',
        'dentist' => null,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['name' => 'Changed Name']);
});

it('does not allow an admin account to be moved to another role', function () {
    $otherAdmin = userTestWithRole('admin', ['email' => 'other.admin@example.com']);

    $response = $this->put(route('users.update', $otherAdmin), [
        'name' => 'Other Admin',
        'email' => 'other.admin@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    $response->assertForbidden();
    expect($otherAdmin->fresh()->hasRole('admin'))->toBeTrue();
});

it('does not allow a password to be changed through the admin edit form', function () {
    $staff = userTestWithRole('staff', ['email' => 'juan@example.com']);

    $response = $this->put(route('users.update', $staff), [
        'name' => 'Juan Staff',
        'email' => 'juan@example.com',
        'role' => 'staff',
        'dentist' => null,
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ]);

    $response->assertForbidden();
});

it('returns 404 when updating a user that does not exist', function () {
    $this->put(route('users.update', 999999), [
        'name' => 'Nobody',
        'email' => 'nobody@example.com',
        'role' => 'staff',
    ])->assertNotFound();
});

it('blocks a staff user from editing accounts', function () {
    $staff = userTestWithRole('staff', ['email' => 'juan@example.com']);
    $target = userTestWithRole('staff', ['email' => 'target@example.com']);
    $this->actingAs($staff);

    $response = $this->put(route('users.update', $target), [
        'name' => 'Changed',
        'email' => 'target@example.com',
        'role' => 'staff',
        'dentist' => null,
    ]);

    $response->assertForbidden();
});

// ---------------------------------------------------------------------------
// deactivating (soft delete)
// ---------------------------------------------------------------------------

it('deactivates a user but keeps the row in the database', function () {
    $staff = userTestWithRole('staff');

    $response = $this->put(route('users.deactivate', $staff));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('message', 'User deactivated successfully.');

    expect($staff->fresh()->is_active)->toBe(IsActiveEnum::from(IsActiveEnum::DEACTIVED->value));
    $this->assertDatabaseHas('users', ['id' => $staff->id]);
});

it('logs a deactivated user out by removing their sessions', function () {
    $staff = userTestWithRole('staff');
    DB::table('sessions')->insert([
        'id' => 'session-for-staff',
        'user_id' => $staff->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => 'payload',
        'last_activity' => time(),
    ]);

    $this->put(route('users.deactivate', $staff));

    $this->assertDatabaseMissing('sessions', ['user_id' => $staff->id]);
});

it('keeps a deactivated dentist\'s dentist details', function () {
    $dentist = userTestWithRole('dentist');
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);

    $this->put(route('users.deactivate', $dentist));

    $this->assertDatabaseHas('dentist_details', ['user_id' => $dentist->id]);
});

it('does not allow an admin account to be deactivated', function () {
    $otherAdmin = userTestWithRole('admin');

    $response = $this->put(route('users.deactivate', $otherAdmin));

    $response->assertForbidden();
    expect($otherAdmin->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('does not allow you to deactivate your own account', function () {
    $response = $this->put(route('users.deactivate', $this->user));

    $response->assertForbidden();
    expect($this->user->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('blocks a staff user from deactivating accounts', function () {
    $staff = userTestWithRole('staff');
    $target = userTestWithRole('staff');
    $this->actingAs($staff);

    $response = $this->put(route('users.deactivate', $target));

    $response->assertForbidden();
    expect($target->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('returns 404 when deactivating a user that does not exist', function () {
    $this->put(route('users.deactivate', 999999))->assertNotFound();
});

// ---------------------------------------------------------------------------
// reactivating
// ---------------------------------------------------------------------------

it('reactivates a deactivated user', function () {
    $staff = userTestWithRole('staff');
    $staff->update(['is_active' => IsActiveEnum::DEACTIVED->value]);

    $response = $this->put(route('users.activate', $staff));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('message', 'User activated successfully.');
    expect($staff->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('can deactivate and reactivate the same user as many times as needed', function () {
    $staff = userTestWithRole('staff');

    $this->put(route('users.deactivate', $staff));
    $this->put(route('users.activate', $staff));
    $this->put(route('users.deactivate', $staff));
    $this->put(route('users.activate', $staff));

    expect($staff->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('keeps a reactivated dentist\'s details and role', function () {
    $dentist = userTestWithRole('dentist');
    DentistDetail::create([
        'user_id' => $dentist->id,
        'specialization' => ['Orthodontics'],
        'available_schedule' => [['day' => 'monday', 'start' => '09:00', 'end' => '17:00']],
        'appointment_limit' => 10,
    ]);
    $this->put(route('users.deactivate', $dentist));

    $this->put(route('users.activate', $dentist));

    expect($dentist->fresh()->hasRole('dentist'))->toBeTrue();
    $this->assertDatabaseHas('dentist_details', ['user_id' => $dentist->id, 'appointment_limit' => 10]);
});

it('does not reactivate a user who is already active', function () {
    $staff = userTestWithRole('staff');

    $response = $this->put(route('users.activate', $staff));

    $response->assertSessionHas('error', 'Only deactivated users can be activated.');
    expect($staff->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
});

it('does not allow you to change the status of your own account', function () {
    $response = $this->put(route('users.activate', $this->user));

    $response->assertForbidden();
});

it('returns 404 when activating a user that does not exist', function () {
    $this->put(route('users.activate', 999999))->assertNotFound();
});