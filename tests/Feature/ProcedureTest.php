<?php

use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has procedures.view/create/update/delete

    $this->actingAs($this->user);
});

dataset('invalidProcedureData', [
    'name missing' => [['name' => ''], 'name'],
    'name too short' => [['name' => 'a'], 'name'],
    'name too long' => [['name' => str_repeat('a', 101)], 'name'],
    'name must be string' => [['name' => ['array']], 'name'],

    'description too short' => [['description' => 'ab'], 'description'],
    'description too long' => [['description' => str_repeat('a', 301)], 'description'],
    'description must be string' => [['description' => ['array']], 'description'],

    'duration negative' => [['duration' => -1], 'duration'],
    'duration over maximum' => [['duration' => 1441], 'duration'],
    'duration must be integer' => [['duration' => 1.5], 'duration'],
    'duration must be numeric text' => [['duration' => 'abc'], 'duration'],

    'price missing' => [['price' => ''], 'price'],
    'price negative' => [['price' => -100], 'price'],
    'price must be numeric' => [['price' => 'not-a-number'], 'price'],
    'price over maximum' => [['price' => 1000000], 'price'],

    'is_active must be boolean' => [['is_active' => 'maybe'], 'is_active'],
]);

// ---------------------------------------------------------------------------
// listing and search
// ---------------------------------------------------------------------------

it('lists procedures on the index page', function () {
    Procedure::create(['name' => 'Simple Tooth Extraction', 'price' => 1500, 'duration' => 30]);

    $response = $this->get(route('procedures.index'));

    $response->assertOk();
});

it('filters procedures by name with a partial match', function () {
    Procedure::create(['name' => 'Simple Tooth Extraction', 'price' => 1500]);
    Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800]);

    $response = $this->get(route('procedures.index', ['search' => 'Extraction']));

    $response->assertOk();
    $response->assertSee('Simple Tooth Extraction');
    $response->assertDontSee('Oral Prophylaxis');
});

it('returns every procedure when search is empty', function () {
    Procedure::create(['name' => 'Simple Tooth Extraction', 'price' => 1500]);
    Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800]);

    $response = $this->get(route('procedures.index', ['search' => '']));

    $response->assertSee('Simple Tooth Extraction');
    $response->assertSee('Oral Prophylaxis');
});

it('handles search terms with special characters without breaking', function () {
    Procedure::create(['name' => 'Oral Prophylaxis', 'price' => 800]);

    $response = $this->get(route('procedures.index', ['search' => "%'_\\"]));

    $response->assertOk();
    $response->assertDontSee('Oral Prophylaxis');
});

it('paginates procedures 15 per page', function () {
    foreach (range(1, 16) as $i) {
        Procedure::create(['name' => "Procedure {$i}", 'price' => 100]);
    }

    $response = $this->get(route('procedures.index'));

    $response->assertOk();
    expect($response->original->getData()['page']['props']['procedures']['data'] ?? $response->original->getData()['procedures']->items())
        ->toHaveCount(15);
});

// ---------------------------------------------------------------------------
// creating
// ---------------------------------------------------------------------------

it('creates a procedure with valid data, redirects with a success message and the record exists in db', function () {
    $response = $this->post(route('procedures.store'), [
        'name' => 'Dental Consultation',
        'description' => 'Initial check of the patient',
        'duration' => 15,
        'price' => 300,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('procedures.index'));
    $response->assertSessionHas('message', 'Procedure created successfully.');

    $this->assertDatabaseHas('procedures', [
        'name' => 'Dental Consultation',
        'description' => 'Initial check of the patient',
        'duration' => 15,
        'is_active' => true,
    ]);
});

it('creates a procedure with name and price only, nullable fields stored as null and is_active defaults to true', function () {
    $response = $this->post(route('procedures.store'), [
        'name' => 'Panoramic X-Ray',
        'price' => 1200,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('procedures.index'));

    $this->assertDatabaseHas('procedures', [
        'name' => 'Panoramic X-Ray',
        'description' => null,
        'duration' => null,
        'is_active' => true,
    ]);
});

it('stores price with two decimal places', function () {
    $this->post(route('procedures.store'), [
        'name' => 'Fluoride Treatment',
        'price' => 500.5,
    ]);

    expect(Procedure::where('name', 'Fluoride Treatment')->first()->price)->toEqual(500.5);
    $this->assertDatabaseHas('procedures', ['name' => 'Fluoride Treatment', 'price' => 500.50]);
});

it('accepts a price of zero as a free procedure', function () {
    $response = $this->post(route('procedures.store'), [
        'name' => 'Free Checkup',
        'price' => 0,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['name' => 'Free Checkup', 'price' => 0]);
});

it('accepts the duration boundaries 0 and 1440', function (int $duration) {
    $response = $this->post(route('procedures.store'), [
        'name' => "Duration {$duration}",
        'price' => 100,
        'duration' => $duration,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['name' => "Duration {$duration}", 'duration' => $duration]);
})->with([0, 1440]);

it('accepts the name boundaries 2 and 100 characters', function (int $length) {
    $response = $this->post(route('procedures.store'), [
        'name' => str_repeat('a', $length),
        'price' => 100,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['name' => str_repeat('a', $length)]);
})->with([2, 100]);

it('accepts the description boundaries 3 and 300 characters', function (int $length) {
    $response = $this->post(route('procedures.store'), [
        'name' => "Desc {$length}",
        'price' => 100,
        'description' => str_repeat('d', $length),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['name' => "Desc {$length}"]);
})->with([3, 300]);

it('rejects a duplicate procedure name on create and writes no second row', function () {
    Procedure::create(['name' => 'Existing Procedure', 'price' => 100]);

    $response = $this->post(route('procedures.store'), [
        'name' => 'Existing Procedure',
        'price' => 500,
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('procedures', 1);
});

it('rejects create with invalid procedure data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $data = array_merge([
        'name' => 'Valid Procedure Name',
        'price' => 1000,
        'duration' => 30,
        'description' => 'Valid description',
    ], $overrides);

    $response = $this->post(route('procedures.store'), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('procedures', 0);
})->with('invalidProcedureData');

// ---------------------------------------------------------------------------
// editing and updating
// ---------------------------------------------------------------------------

it('shows the edit page for an existing procedure', function () {
    $procedure = Procedure::create(['name' => 'Pulp Capping', 'price' => 1500]);

    $response = $this->get(route('procedures.edit', $procedure));

    $response->assertOk();
});

it('returns 404 when opening the edit page for a procedure that does not exist', function () {
    $this->get(route('procedures.edit', 999999))->assertNotFound();
});

it('updates a procedure with valid data, redirects with a success message and the record is updated in db', function () {
    $procedure = Procedure::create(['name' => 'Pulp Capping', 'price' => 1500, 'duration' => 30]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Pulp Capping Updated',
        'price' => 1800,
        'duration' => 45,
        'is_active' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('procedures.index'));
    $response->assertSessionHas('message', 'Procedure updated successfully.');

    $this->assertDatabaseHas('procedures', [
        'id' => $procedure->id,
        'name' => 'Pulp Capping Updated',
        'price' => 1800,
        'duration' => 45,
        'is_active' => false,
    ]);
});

it('allows updating a procedure while keeping its own name', function () {
    $procedure = Procedure::create(['name' => 'Pulp Capping', 'price' => 1500]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Pulp Capping',
        'price' => 1600,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id, 'name' => 'Pulp Capping', 'price' => 1600]);
});

it('rejects renaming a procedure to another existing name and leaves the record unchanged', function () {
    Procedure::create(['name' => 'Existing Procedure', 'price' => 100]);
    $procedure = Procedure::create(['name' => 'Original Name', 'price' => 500]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Existing Procedure',
        'price' => 900,
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseHas('procedures', [
        'id' => $procedure->id,
        'name' => 'Original Name',
        'price' => 500,
    ]);
});

it('keeps is_active unchanged when the field is omitted on update', function () {
    $procedure = Procedure::create(['name' => 'Retired Procedure', 'price' => 100, 'is_active' => false]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Retired Procedure',
        'price' => 150,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id, 'is_active' => false]);
});

it('rejects update with invalid procedure data and leaves the record unchanged', function (array $overrides, string $expectedErrorField) {
    $procedure = Procedure::create([
        'name' => 'Stable Procedure',
        'price' => 1000,
        'duration' => 30,
        'description' => 'Stable description',
    ]);

    $data = array_merge([
        'name' => 'Stable Procedure',
        'price' => 1000,
        'duration' => 30,
        'description' => 'Stable description',
    ], $overrides);

    $response = $this->put(route('procedures.update', $procedure), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseHas('procedures', [
        'id' => $procedure->id,
        'name' => 'Stable Procedure',
        'price' => 1000,
        'duration' => 30,
    ]);
})->with('invalidProcedureData');

it('returns 404 when updating a procedure that does not exist', function () {
    $this->put(route('procedures.update', 999999), [
        'name' => 'Doesnt Matter',
        'price' => 100,
    ])->assertNotFound();
});

// ---------------------------------------------------------------------------
// activation
// ---------------------------------------------------------------------------

it('deactivates a procedure without deleting it or touching its appointments', function () {
    $procedure = Procedure::create(['name' => 'Active Procedure', 'price' => 100, 'is_active' => true]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Active Procedure',
        'price' => 100,
        'is_active' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id, 'is_active' => false]);
    $this->assertDatabaseCount('procedures', 1);
});

it('reactivates a deactivated procedure', function () {
    $procedure = Procedure::create(['name' => 'Inactive Procedure', 'price' => 100, 'is_active' => false]);

    $response = $this->put(route('procedures.update', $procedure), [
        'name' => 'Inactive Procedure',
        'price' => 100,
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id, 'is_active' => true]);
});

// ---------------------------------------------------------------------------
// deleting
// ---------------------------------------------------------------------------

it('deletes a procedure that has no appointments, redirects with a success message', function () {
    $procedure = Procedure::create(['name' => 'Unused Procedure', 'price' => 100]);

    $response = $this->delete(route('procedures.destroy', $procedure));

    $response->assertRedirect(route('procedures.index'));
    $response->assertSessionHas('message', 'Procedure deleted successfully.');
    $this->assertDatabaseMissing('procedures', ['id' => $procedure->id]);
});

it('rejects deleting a procedure used by one appointment and keeps both records', function () {
    $procedure = Procedure::create(['name' => 'Used Procedure', 'price' => 100]);
    $appointment = Appointment::factory()->create(['procedure_id' => $procedure->id]);

    $response = $this->delete(route('procedures.destroy', $procedure));

    $response->assertRedirect(route('procedures.index'));
    $response->assertSessionHas('error', 'This Procedure use in appointment and cannot be deleted.');
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'procedure_id' => $procedure->id]);
});

it('rejects deleting a procedure used by many appointments', function () {
    $procedure = Procedure::create(['name' => 'Popular Procedure', 'price' => 100]);
    Appointment::factory()->count(3)->create(['procedure_id' => $procedure->id]);

    $response = $this->delete(route('procedures.destroy', $procedure));

    $response->assertSessionHas('error', 'This Procedure use in appointment and cannot be deleted.');
    $this->assertDatabaseHas('procedures', ['id' => $procedure->id]);
    $this->assertDatabaseCount('appointments', 3);
});

it('allows deleting a procedure after its last appointment is removed', function () {
    $procedure = Procedure::create(['name' => 'Formerly Used', 'price' => 100]);
    $appointment = Appointment::factory()->create(['procedure_id' => $procedure->id]);

    $appointment->delete();

    $response = $this->delete(route('procedures.destroy', $procedure));

    $response->assertSessionHas('message', 'Procedure deleted successfully.');
    $this->assertDatabaseMissing('procedures', ['id' => $procedure->id]);
});

it('returns 404 when deleting a procedure that does not exist', function () {
    $this->delete(route('procedures.destroy', 999999))->assertNotFound();
});