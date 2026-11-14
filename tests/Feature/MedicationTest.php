<?php

use App\Enums\IsActiveEnum;
use App\Models\Medication;
use App\Models\TreatmentPrescriptionDetails;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff');

    $this->actingAs($this->user);
});

dataset('invalidMedicationData', [
    'name missing' => [['name' => ''], 'name'],
    'name too short' => [['name' => 'a'], 'name'],
    'name too long' => [['name' => str_repeat('a', 151)], 'name'],
    'name must be string' => [['name' => ['array']], 'name'],

    'generic_name too long' => [['generic_name' => str_repeat('a', 151)], 'generic_name'],
    'generic_name must be string' => [['generic_name' => ['array']], 'generic_name'],

    'dosage_form too long' => [['dosage_form' => str_repeat('a', 76)], 'dosage_form'],
    'dosage_form must be string' => [['dosage_form' => ['array']], 'dosage_form'],

    'strength too long' => [['strength' => str_repeat('a', 51)], 'strength'],
    'strength must be string' => [['strength' => ['array']], 'strength'],
]);

// ---------------------------------------------------------------------------
// listing and search
// ---------------------------------------------------------------------------

it('lists medications on the index page', function () {
    Medication::create(['name' => 'Amoxicillin']);

    $response = $this->get(route('medications.index'));

    $response->assertOk();
});

it('orders medications by name', function () {
    Medication::create(['name' => 'Cefalexin']);
    Medication::create(['name' => 'Amoxicillin']);

    $response = $this->get(route('medications.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Medications/Index')
        ->where('medications.data.0.name', 'Amoxicillin')
        ->where('medications.data.1.name', 'Cefalexin')
    );
});

it('filters medications by name', function () {
    Medication::create(['name' => 'Amoxicillin']);
    Medication::create(['name' => 'Ibuprofen']);

    $response = $this->get(route('medications.index', ['search' => 'Amox']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('medications.data', 1)
        ->where('medications.data.0.name', 'Amoxicillin')
        ->where('filters.search', 'Amox')
    );
});

it('filters medications by generic name', function () {
    Medication::create(['name' => 'Amoxicillin', 'generic_name' => 'Amoxicillin trihydrate']);
    Medication::create(['name' => 'Ibuprofen']);

    $response = $this->get(route('medications.index', ['search' => 'trihydrate']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('medications.data', 1)
        ->where('medications.data.0.name', 'Amoxicillin')
    );
});

it('returns every medication when search is empty', function () {
    Medication::create(['name' => 'Amoxicillin']);
    Medication::create(['name' => 'Ibuprofen']);

    $response = $this->get(route('medications.index', ['search' => '']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('medications.data', 2)
    );
});

it('paginates medications 15 per page', function () {
    foreach (range(1, 16) as $i) {
        Medication::create(['name' => "Medication {$i}"]);
    }

    $response = $this->get(route('medications.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('medications.data', 15)
        ->where('medications.total', 16)
    );
});

it('rejects a search term longer than the search rule allows', function () {
    $response = $this->get(route('medications.index', ['search' => str_repeat('a', 300)]));

    $response->assertSessionHasErrors('search');
});

// ---------------------------------------------------------------------------
// creating
// ---------------------------------------------------------------------------

it('creates a medication with valid data, redirects with a success message and the record exists in db', function () {
    $response = $this->post(route('medications.store'), [
        'name' => 'Amoxicillin',
        'generic_name' => 'Amoxicillin trihydrate',
        'dosage_form' => 'Capsule',
        'strength' => '500 mg',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('medications.index'));
    $response->assertSessionHas('message', 'Medication created successfully.');

    $this->assertDatabaseHas('medications', [
        'name' => 'Amoxicillin',
        'generic_name' => 'Amoxicillin trihydrate',
        'dosage_form' => 'Capsule',
        'strength' => '500 mg',
    ]);
});

it('creates a medication with name only and the optional fields stored as null', function () {
    $response = $this->post(route('medications.store'), [
        'name' => 'Ibuprofen',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('medications.index'));

    $this->assertDatabaseHas('medications', [
        'name' => 'Ibuprofen',
        'generic_name' => null,
        'dosage_form' => null,
        'strength' => null,
    ]);
});

it('accepts the name boundaries 2 and 150 characters', function (int $length) {
    $response = $this->post(route('medications.store'), [
        'name' => str_repeat('a', $length),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('medications', ['name' => str_repeat('a', $length)]);
})->with([2, 150]);

it('accepts the optional field boundaries', function () {
    $response = $this->post(route('medications.store'), [
        'name' => 'Boundary Medication',
        'generic_name' => str_repeat('g', 150),
        'dosage_form' => str_repeat('d', 75),
        'strength' => str_repeat('s', 50),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('medications', ['name' => 'Boundary Medication']);
});

it('treats an empty optional field as null', function () {
    $this->post(route('medications.store'), [
        'name' => 'Paracetamol',
        'generic_name' => '',
        'strength' => '',
    ]);

    $this->assertDatabaseHas('medications', [
        'name' => 'Paracetamol',
        'generic_name' => null,
        'strength' => null,
    ]);
});

// NOTE: i dont not have unique on this
it('rejects a duplicate medication name on create and writes no second row', function () {
    Medication::create(['name' => 'Amoxicillin']);

    $response = $this->post(route('medications.store'), [
        'name' => 'Amoxicillin',
        'strength' => '250 mg',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('medications', 1);
});

it('rejects create with invalid medication data and writes nothing', function (array $overrides, string $expectedErrorField) {
    $data = array_merge([
        'name' => 'Valid Medication',
        'generic_name' => 'Valid generic',
        'dosage_form' => 'Tablet',
        'strength' => '100 mg',
    ], $overrides);

    $response = $this->post(route('medications.store'), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseCount('medications', 0);
})->with('invalidMedicationData');

// ---------------------------------------------------------------------------
// editing and updating
// ---------------------------------------------------------------------------

it('shows the edit page with only the editable fields', function () {
    $medication = Medication::create([
        'name' => 'Amoxicillin',
        'generic_name' => 'Amoxicillin trihydrate',
        'dosage_form' => 'Capsule',
        'strength' => '500 mg',
    ]);

    $response = $this->get(route('medications.edit', $medication));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Medications/Edit')
        ->where('medication.id', $medication->id)
        ->where('medication.name', 'Amoxicillin')
        ->where('medication.generic_name', 'Amoxicillin trihydrate')
        ->where('medication.dosage_form', 'Capsule')
        ->where('medication.strength', '500 mg')
        ->missing('medication.created_at')
        ->missing('medication.updated_at')
    );
});

it('returns 404 when opening the edit page for a medication that does not exist', function () {
    $this->get(route('medications.edit', 999999))->assertNotFound();
});

it('updates a medication with valid data, redirects with a success message and the record is updated in db', function () {
    $medication = Medication::create(['name' => 'Amoxicillin', 'strength' => '250 mg']);

    $response = $this->put(route('medications.update', $medication), [
        'name' => 'Amoxicillin Forte',
        'generic_name' => 'Amoxicillin trihydrate',
        'dosage_form' => 'Capsule',
        'strength' => '500 mg',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('medications.index'));
    $response->assertSessionHas('message', 'Medication updated successfully.');

    $this->assertDatabaseHas('medications', [
        'id' => $medication->id,
        'name' => 'Amoxicillin Forte',
        'generic_name' => 'Amoxicillin trihydrate',
        'dosage_form' => 'Capsule',
        'strength' => '500 mg',
    ]);
});

it('allows updating a medication while keeping its own name', function () {
    $medication = Medication::create(['name' => 'Ibuprofen']);

    $response = $this->put(route('medications.update', $medication), [
        'name' => 'Ibuprofen',
        'strength' => '400 mg',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('medications', ['id' => $medication->id, 'name' => 'Ibuprofen', 'strength' => '400 mg']);
});

it('clears an optional field when it is sent as null', function () {
    $medication = Medication::create(['name' => 'Ibuprofen', 'strength' => '400 mg']);

    $response = $this->put(route('medications.update', $medication), [
        'name' => 'Ibuprofen',
        'strength' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('medications', ['id' => $medication->id, 'strength' => null]);
});

it('rejects renaming a medication to another existing name and leaves the record unchanged', function () {
    Medication::create(['name' => 'Existing Medication']);
    $medication = Medication::create(['name' => 'Original Name', 'strength' => '100 mg']);

    $response = $this->put(route('medications.update', $medication), [
        'name' => 'Existing Medication',
        'strength' => '200 mg',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseHas('medications', [
        'id' => $medication->id,
        'name' => 'Original Name',
        'strength' => '100 mg',
    ]);
});

it('rejects update with invalid medication data and leaves the record unchanged', function (array $overrides, string $expectedErrorField) {
    $medication = Medication::create([
        'name' => 'Stable Medication',
        'generic_name' => 'Stable generic',
        'dosage_form' => 'Tablet',
        'strength' => '100 mg',
    ]);

    $data = array_merge([
        'name' => 'Stable Medication',
        'generic_name' => 'Stable generic',
        'dosage_form' => 'Tablet',
        'strength' => '100 mg',
    ], $overrides);

    $response = $this->put(route('medications.update', $medication), $data);

    $response->assertSessionHasErrors($expectedErrorField);
    $this->assertDatabaseHas('medications', [
        'id' => $medication->id,
        'name' => 'Stable Medication',
        'strength' => '100 mg',
    ]);
})->with('invalidMedicationData');

it('returns 404 when updating a medication that does not exist', function () {
    $this->put(route('medications.update', 999999), [
        'name' => 'Doesnt Matter',
    ])->assertNotFound();
});

// ---------------------------------------------------------------------------
// deleting
// ---------------------------------------------------------------------------

it('deletes a medication that is not used in any treatment, redirects with a success message', function () {
    $medication = Medication::create(['name' => 'Unused Medication']);

    $response = $this->delete(route('medications.destroy', $medication));

    $response->assertRedirect(route('medications.index'));
    $response->assertSessionHas('message', 'Medication deleted successfully.');
    $this->assertDatabaseMissing('medications', ['id' => $medication->id]);
});

it('rejects deleting a medication used by a treatment prescription and keeps both records', function () {
    $medication = Medication::create(['name' => 'Used Medication']);
    $prescription = TreatmentPrescriptionDetails::factory()->create(['medication_id' => $medication->id]);

    $response = $this->delete(route('medications.destroy', $medication));

    $response->assertRedirect(route('medications.index'));
    $response->assertSessionHas('error', 'This Medication use in treatment and cannot be deleted.');
    $this->assertDatabaseHas('medications', ['id' => $medication->id]);
    $this->assertDatabaseHas('treatment_prescription_details', ['id' => $prescription->id, 'medication_id' => $medication->id]);
});

it('rejects deleting a medication used by many treatment prescriptions', function () {
    $medication = Medication::create(['name' => 'Popular Medication']);
    TreatmentPrescriptionDetails::factory()->count(3)->create(['medication_id' => $medication->id]);

    $response = $this->delete(route('medications.destroy', $medication));

    $response->assertSessionHas('error', 'This Medication use in treatment and cannot be deleted.');
    $this->assertDatabaseHas('medications', ['id' => $medication->id]);
    $this->assertDatabaseCount('treatment_prescription_details', 3);
});

it('allows deleting a medication after its last treatment prescription is removed', function () {
    $medication = Medication::create(['name' => 'Formerly Used']);
    $prescription = TreatmentPrescriptionDetails::factory()->create(['medication_id' => $medication->id]);

    $prescription->delete();

    $response = $this->delete(route('medications.destroy', $medication));

    $response->assertSessionHas('message', 'Medication deleted successfully.');
    $this->assertDatabaseMissing('medications', ['id' => $medication->id]);
});

it('returns 404 when deleting a medication that does not exist', function () {
    $this->delete(route('medications.destroy', 999999))->assertNotFound();
});