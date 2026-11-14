<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Enums\IsApproveEnum;
use App\Models\Appointment;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);

    User::query()->update([
        'email_verified_at' => now(),
        'is_active' => IsActiveEnum::ACTIVE->value,
    ]);
});

/*
|--------------------------------------------------------------------------
| Helpers: tests only name a route, these work out the rest
|--------------------------------------------------------------------------
*/

function rpStaff(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

function rpPatient(string $email = 'patient@gmail.com'): Patient
{
    return Patient::where('email', $email)->firstOrFail();
}

function rpMakeUser(?string $role = null, array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'is_active' => IsActiveEnum::ACTIVE->value,
    ], $overrides));

    if ($role) {
        $user->assignRole($role);
    }

    return $user;
}

function rpOtherPatient(): Patient
{
    $p = Patient::create([
        'first_name' => 'Other', 'last_name' => 'Patient',
        'gender' => GenderEnum::MALE->value, 'date_of_birth' => '1990-01-01',
        'email' => 'other.patient@example.com', 'contact_number' => '09170000000',
        'address' => 'Test', 'is_active' => IsActiveEnum::ACTIVE->value,
        'password' => 'MyPassword123',
    ]);
    $p->assignRole('patient');

    return $p;
}

/** Any is_active value that is not ACTIVE, whatever your enum calls it. */
function rpInactive(): string
{
    return collect(IsActiveEnum::cases())
        ->first(fn ($c) => $c !== IsActiveEnum::ACTIVE)
        ->value;
}

function rpFlushPermissionCache(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/** Fills required columns from the table schema, so you never list columns by hand. */
function rpMake(string $model, array $given = []): int
{
    $table = (new $model)->getTable();
    $attrs = $given;

    foreach (Schema::getColumns($table) as $col) {
        $name = $col['name'];

        if (array_key_exists($name, $attrs)
            || $col['auto_increment'] || $col['nullable'] || $col['default'] !== null
            || in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
            continue;
        }

        $type = strtolower($col['type_name']);

        $attrs[$name] = match (true) {
            str_ends_with($name, '_id') => rpRecord(lcfirst(str_replace('_', '', ucwords(substr($name, 0, -3), '_')))),
            str_contains($type, 'int') => 1,
            str_contains($type, 'decimal'), str_contains($type, 'float'), str_contains($type, 'double') => 10,
            str_contains($type, 'bool') => true,
            $type === 'date' => now()->addDays(7)->format('Y-m-d'),
            str_contains($type, 'time') => now()->format('Y-m-d H:i:s'),
            str_contains($type, 'json') => '[]',
            default => 'Test ' . $name,
        };
    }

    return $model::unguarded(fn () => $model::create($attrs))->id;
}

/**
 * One real record per route parameter name, created on first use.
 * If a route has a {param} not listed in the match, add a case for it here once.
 * Model binding runs before permission checks, so a missing record gives 404, not 403.
 */
function rpRecord(string $param): int
{
    $cache = app()->bound('rpCache') ? app('rpCache') : [];

    if (isset($cache[$param])) {
        return $cache[$param];
    }

    $dentist = rpStaff('dentist@gmail.com');
    $appointment = fn (string $time) => Appointment::create([
        'patient_id' => rpPatient()->id,
        'dentist_id' => $dentist->id,
        'procedure_id' => rpRecord('procedure'),
        'staff_id' => rpStaff('staff@gmail.com')->id,
        'appointment_date' => now()->addDays(7)->format('Y-m-d'),
        'appointment_time' => $time,
        'status' => 'scheduled',
        'is_approve' => IsApproveEnum::Pending->value,
    ])->id;

    $id = match ($param) {
        'user' => rpMakeUser('staff')->id,
        'patient' => rpPatient()->id,
        'procedure' => Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800, 'duration' => 30])->id,
        'appointment' => $appointment('09:00:00'),
        'treatment' => Treatment::create([
            'appointment_id' => $appointment('10:00:00'),
            'user_id' => $dentist->id,
            'duration_minutes' => 30,
            'summary' => 'Done',
            'follow_up_schedule' => now()->addWeek()->format('Y-m-d'),
        ])->id,
        'medication' => rpMake(Medication::class),
        'inventoryItem' => rpMake(InventoryItem::class),
        'inventory' => Inventory::factory()->create([
            'staff_id' => rpStaff('staff@gmail.com')->id,
        ])->id,
        'staff' => rpStaff('staff@gmail.com')->id,
        'dentist' => $dentist->id,
        default => 999999, // unknown parameter: add a case above
    };

    $cache = app()->bound('rpCache') ? app('rpCache') : [];
    $cache[$param] = $id;
    app()->instance('rpCache', $cache);

    return $id;
}

/** Several ids at once, for tests that need to check records afterwards. */
function rpRecords(): array
{
    return collect(['user', 'patient', 'procedure', 'appointment', 'treatment'])
        ->mapWithKeys(fn ($p) => [$p => rpRecord($p)])
        ->all();
}

/** Calls a route by NAME as the given actor. URL, HTTP method and ids are looked up. */
function rpCall(?object $actor, string $routeName, string $guard = 'web')
{
    $route = Route::getRoutes()->getByName($routeName);
    expect($route)->not->toBeNull("Route [{$routeName}] does not exist");

    $params = collect($route->parameterNames())->mapWithKeys(fn ($p) => [$p => rpRecord($p)])->all();

    $test = $actor ? test()->actingAs($actor, $guard) : test();

    return $test->call($route->methods()[0], route($routeName, $params, false));
}

/** Allowed = got past the permission check: not 403, not 404, not sent to a login page. */
function rpExpectAllowed($response): void
{
    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($response->isRedirection()) {
        expect((string) $response->headers->get('Location'))->not->toContain('/login');
    }
}

function rpExpectBlocked($response): void
{
    $response->assertForbidden();
}

/*
|--------------------------------------------------------------------------
| 1. THE MATRIX: route name => [admin, staff, dentist]   true = allowed
|--------------------------------------------------------------------------
| index = view list, create/store = add, edit/update = change, destroy = delete.
| When you add a policy, nothing here changes unless the rule itself changes.
*/

$matrix = [
    'dashboard'                       => [true,  true,  true],

    'users.index'                     => [true,  false, false],
    'users.create'                    => [true,  false, false],
    'users.store'                     => [true,  false, false],
    'users.edit'                      => [true,  false, false],
    'users.update'                    => [true,  false, false],
    'users.deactivate'                => [true,  false, false],
    'users.activate'                  => [true,  false, false],

    'patients.index'                  => [false, true,  true],
    'patients.show'                   => [false, true,  true],
    'patients.questionnaire'          => [false, true,  true],
    'patients.create'                 => [false, true,  false],
    'patients.store'                  => [false, true,  false],
    'patients.edit'                   => [false, true,  false],
    'patients.update'                 => [false, true,  false],
    'patients.deactivate'             => [false, true,  false],
    'patients.activate'               => [false, true,  false],

    'appointments.index'              => [false, true,  true],
    'appointments.questionnaire.show' => [false, true,  true],
    'appointments.create'             => [false, true,  false],
    'appointments.store'              => [false, true,  false],
    'appointments.edit'               => [false, true,  false],
    'appointments.update'             => [false, true,  false],
    'appointments.destroy'            => [false, true,  false],
    'appointments.day_statuses'       => [false, true,  false],
    'appointments.open_slots'         => [false, true,  false],

    'appointment_approvals.index'     => [false, true,  true],
    'appointment_approvals.approve'   => [false, true,  false],
    'appointment_approvals.reject'    => [false, true,  false],

    'treatments.index'                => [false, true,  true],
    'treatments.create'               => [false, false, true],
    'treatments.store'                => [false, false, true],
    'treatments.edit'                 => [false, false, true],
    'treatments.update'               => [false, false, true],
    'treatments.destroy'              => [false, false, true],

    'procedures.index'                => [false, true,  true],
    'procedures.create'               => [false, true,  false],
    'procedures.store'                => [false, true,  false],
    'procedures.edit'                 => [false, true,  false],
    'procedures.update'               => [false, true,  false],
    'procedures.destroy'              => [false, true,  false],

    'medications.index'               => [false, true,  true],
    'medications.create'              => [false, true,  false],
    'medications.store'               => [false, true,  false],
    'medications.edit'                => [false, true,  false],
    'medications.update'              => [false, true,  false],
    'medications.destroy'             => [false, true,  false],

    'inventory_items.index'           => [false, true,  false],
    'inventory_items.create'          => [false, true,  false],
    'inventory_items.store'           => [false, true,  false],
    'inventory_items.edit'            => [false, true,  false],
    'inventory_items.update'          => [false, true,  false],
    'inventory_items.destroy'         => [false, true,  false],

    'inventories.index'               => [false, true,  false],
    'inventories.create'              => [false, true,  false],
    'inventories.store'               => [false, true,  false],
    'inventories.edit'                => [false, true,  false],
    'inventories.update'              => [false, true,  false],
    'inventories.destroy'             => [false, true,  false],

    'reports.index'                   => [true,  true,  false],
    'reports.export'                  => [true,  true,  false],
    'reports.patient'                 => [true,  true,  false],
];

$roles = ['admin' => 'admin@gmail.com', 'staff' => 'staff@gmail.com', 'dentist' => 'dentist@gmail.com'];

foreach ($matrix as $routeName => $allowedFor) {
    foreach (array_keys($roles) as $i => $role) {
        $allowed = $allowedFor[$i];

        it(($allowed ? 'allows' : 'blocks') . " {$role} on {$routeName}", function () use ($roles, $role, $routeName, $allowed) {
            $response = rpCall(rpStaff($roles[$role]), $routeName);

            $allowed ? rpExpectAllowed($response) : $response->assertForbidden();
        });
    }

    it("keeps a guest out of {$routeName}", function () use ($routeName) {
        rpCall(null, $routeName)->assertRedirect(route('login'));
    });

    it("keeps a logged-in patient out of {$routeName}", function () use ($routeName) {
        rpCall(rpPatient(), $routeName, 'patient')->assertRedirect(route('login'));
    });
}

/*
|--------------------------------------------------------------------------
| 2. PATIENT PORTAL: route name only
|--------------------------------------------------------------------------
*/

$patientRoutes = [
    'patient.dashboard',
    'patient.appointments.index',
    'patient.appointments.create',
    'patient.appointments.store',
    'patient.appointments.edit',
    'patient.appointments.update',
    'patient.appointments.cancel',
    'patient.treatments.index',
    'patient.questionnaire.edit',
    'patient.questionnaire.store',
];

foreach ($patientRoutes as $routeName) {
    it("allows a patient on {$routeName}", function () use ($routeName) {
        rpExpectAllowed(rpCall(rpPatient(), $routeName, 'patient'));
    });

    it("keeps a guest out of {$routeName}", function () use ($routeName) {
        rpCall(null, $routeName)->assertRedirect(route('patient.login'));
    });

    foreach ($roles as $role => $email) {
        it("keeps {$role} out of {$routeName}", function () use ($email, $routeName) {
            rpCall(rpStaff($email), $routeName)->assertRedirect(route('patient.login'));
        });
    }
}

/*
|--------------------------------------------------------------------------
| 3. OWNERSHIP (this is what your policies will enforce)
|--------------------------------------------------------------------------
| Patient B calls patient A's records. If this fails today, it is a real
| finding, and passing is the proof that the policy works.
| 403 and 404 both count as blocked.
*/

it('stops a patient from using another patient\'s appointment', function (string $routeName) {
    $other = rpOtherPatient();

    $response = rpCall($other, $routeName, 'patient'); // {appointment} belongs to the seeded patient

    expect($response->getStatusCode())->toBeIn([403, 404]);
})->with([
    'patient.appointments.edit',
    'patient.appointments.update',
    'patient.appointments.cancel',
]);

it('does not change another patient\'s appointment when blocked', function () {
    $other = rpOtherPatient();
    rpCall($other, 'patient.appointments.cancel', 'patient');

    $appointment = Appointment::find(rpRecord('appointment'));
    expect($appointment->status)->toBe('scheduled');
});

/*
|--------------------------------------------------------------------------
| 4. EDGE CASES: roles and permissions changing
|--------------------------------------------------------------------------
*/

it('blocks a staff account with no role', function (string $routeName) {
    rpExpectBlocked(rpCall(rpMakeUser(), $routeName));
})->with(['users.index', 'patients.index', 'appointments.index', 'treatments.index', 'reports.index']);

it('blocks a patient with no role', function (string $routeName) {
    $patient = rpPatient();
    $patient->syncRoles([]);
    rpFlushPermissionCache();

    rpCall($patient->fresh(), $routeName, 'patient')->assertForbidden();
})->with(['patient.dashboard', 'patient.appointments.index', 'patient.treatments.index']);

it('takes access away as soon as a role is removed', function () {
    $user = rpMakeUser('staff');
    rpExpectAllowed(rpCall($user, 'patients.index'));

    $user->removeRole('staff');
    rpFlushPermissionCache();

    rpCall($user->fresh(), 'patients.index')->assertForbidden();
});

it('lets a direct permission work without a role, and only for that action', function () {
    $user = rpMakeUser();
    $user->givePermissionTo('patients.view');
    rpFlushPermissionCache();

    rpExpectAllowed(rpCall($user->fresh(), 'patients.index'));
    rpCall($user->fresh(), 'patients.create')->assertForbidden();
});

it('does not let a deactivated staff account in', function () {
    $user = rpMakeUser('staff', ['is_active' => rpInactive()]);

    expect(rpCall($user, 'patients.index')->getStatusCode())->not->toBe(200);
});

it('does not give a patient staff access when a staff account shares the email', function () {
    $patient = rpPatient();
    rpMakeUser('admin', ['email' => $patient->email]);

    rpCall($patient, 'users.index', 'patient')->assertRedirect(route('login'));
});

it('does not give staff patient access when a patient shares the email', function () {
    $admin = rpMakeUser('admin', ['email' => rpPatient()->email]);

    rpCall($admin, 'patient.dashboard')->assertRedirect(route('patient.login'));
});

/*
|--------------------------------------------------------------------------
| 5. EDGE CASES: privilege escalation (nothing may change)
|--------------------------------------------------------------------------
*/

it('stops staff from promoting a user to admin', function () {
    $target = User::find(rpRecord('user'));

    test()->actingAs(rpStaff('staff@gmail.com'), 'web')
        ->put(route('users.update', $target), ['role' => 'admin', 'roles' => ['admin']])
        ->assertForbidden();

    expect($target->fresh()->hasRole('admin'))->toBeFalse();
});

it('stops staff and dentist from deactivating the admin', function (string $email) {
    $admin = rpStaff('admin@gmail.com');

    test()->actingAs(rpStaff($email), 'web')
        ->put(route('users.deactivate', $admin))
        ->assertForbidden();

    expect($admin->fresh()->is_active)->toBe(IsActiveEnum::ACTIVE);
})->with(['staff@gmail.com', 'dentist@gmail.com']);

it('stops a dentist from deleting records they may only view', function () {
    $ids = rpRecords();

    rpCall(rpStaff('dentist@gmail.com'), 'procedures.destroy')->assertForbidden();
    rpCall(rpStaff('dentist@gmail.com'), 'appointments.destroy')->assertForbidden();

    expect(Procedure::find($ids['procedure']))->not->toBeNull();
    expect(Appointment::find($ids['appointment'])->status)->not->toBe('cancelled');
});

it('stops staff from deleting a treatment', function () {
    rpCall(rpStaff('staff@gmail.com'), 'treatments.destroy')->assertForbidden();

    expect(Treatment::find(rpRecord('treatment')))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| 6. COVERAGE GUARD: every named route is either tested here or known open
|--------------------------------------------------------------------------
| Fails when you add a new route and forget to add it to the matrix, which
| is the safety net that replaces re-testing everything by hand.
*/

it('has every staff and patient route in the matrix above', function () use ($matrix, $patientRoutes) {
    $known = array_merge(array_keys($matrix), $patientRoutes, [
        // deliberately not in the matrix (no permission to test, or covered elsewhere)
        'patient.logout',
        'patient.appointments.day_statuses', 'patient.appointments.open_slots',
        'patient.appointments.questionnaire', 'patient.appointments.questionnaire.save',
        'patient.appointments.questionnaire.edit', 'patient.appointments.questionnaire.update',
        'appointments.questionnaire', 'appointments.questionnaire.save', 'appointments.questionnaire.skip',
        // framework / Fortify routes
        'logout', 'password.confirm', 'password.confirmation', 'password.confirm.store',
    ]);

    $untested = collect(Route::getRoutes())
        ->filter(fn ($r) => $r->getName()
            && collect($r->gatherMiddleware())->contains(fn ($m) => in_array($m, ['auth:web', 'auth:patient'], true)))
        ->map(fn ($r) => $r->getName())
        ->reject(fn ($name) => in_array($name, $known, true))
        ->values()
        ->all();

    expect($untested)->toBe([]);
});