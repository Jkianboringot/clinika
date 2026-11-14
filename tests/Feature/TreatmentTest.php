<?php

use App\Enums\IsActiveEnum;
use App\Mail\TreatmentSummaryMail;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('dentist'); // dentist has treatments.view/create/update
    $this->actingAs($this->user);

    $this->patient = treatTestPatient();
    $this->procedure = Procedure::create(['name' => 'Simple Tooth Extraction', 'price' => 1500, 'duration' => 30]);
    $this->medication = Medication::create(['name' => 'Amoxicillin', 'strength' => '500 mg']);
    $this->appointment = treatTestAppointment($this->patient, $this->user, $this->procedure);
});


/**
 * Creates a patient with the fields the patient record needs.
 */
function treatTestPatient(string $email = 'juan.treat@example.com'): Patient
{
    return Patient::create([
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'gender' => \App\Enums\GenderEnum::cases()[0]->value,
        'date_of_birth' => '1980-01-01',
        'email' => $email,
        'contact_number' => '09171234567',
        'password' => 'MyPassword123',
    ]);
}

/**
 * An approved, scheduled appointment with the given dentist. Override any column as needed.
 */
function treatTestAppointment(Patient $patient, User $dentist, Procedure $procedure, array $overrides = []): Appointment
{
    return Appointment::factory()->create(array_merge([
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'procedure_id' => $procedure->id,
        'appointment_date' => now()->addDays(3)->format('Y-m-d'),
        'appointment_time' => '09:00:00',
        'status' => 'scheduled',
        'is_approve' => 'approve',
    ], $overrides));
}

/**
 * A valid treatment payload. Items and prescriptions are passed in so each test controls them.
 */
function treatTestPayload(int $medicationId, array $items = [], array $overrides = []): array
{
    if ($items === [] && !array_key_exists('itemList', $overrides)) {
        $default = treatTestItem('Default Item ' . uniqid());
        treatTestStock($default, 100);
        $items = [['item_id' => $default->id, 'quantity' => 1]];
    }

    return array_merge([
        'follow_up_schedule' => now()->addWeek()->format('Y-m-d'),
        'duration_minutes' => 30,
        'summary' => 'Extraction done, no complications',
        'prescriptionlist' => [[
            'medication_id' => $medicationId,
            'dosage' => '500 mg',
            'frequency' => 'Three times a day',
            'duration_days' => 5,
            'dentist_note' => 'Take after meals',
        ]],
        'itemList' => $items,
    ], $overrides);
}

/**
 * Adds stock through the real InventoryService, the same way the app does.
 */
function treatTestStock(InventoryItem $item, float $quantity): void
{
    app(InventoryService::class)->inventoryStore([
        'itemList' => [['item_id' => $item->id, 'quantity' => $quantity]],
    ]);
}

function treatTestItem(string $name, ?float $minimum = null, ?float $critical = null): InventoryItem
{
    return InventoryItem::create([
        'item_name' => $name,
        'minimum_stock' => $minimum,
        'critical_stock' => $critical,
    ]);
}

// ---------------------------------------------------------------------------
// opening the treatment page
// ---------------------------------------------------------------------------

it('opens the treatment page for an approved appointment, showing the patient and the procedure', function () {
    $response = $this->get(route('treatments.create', $this->appointment));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Treatments/Create')
        ->where('appointment.id', $this->appointment->id)
        ->where('appointment.patient.id', $this->patient->id)
        ->has('procedures', 1)
    );
});

it('does not let staff open the treatment page', function () {
    $staff = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $staff->assignRole('staff');
    $this->actingAs($staff);

    $this->get(route('treatments.create', $this->appointment))->assertForbidden();
});

it('does not let staff save a treatment', function () {
    $staff = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $staff->assignRole('staff');
    $this->actingAs($staff);

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id))
        ->assertForbidden();
    $this->assertDatabaseCount('treatments', 0);
});

it('does not let a dentist treat another dentist\'s appointment', function () {
    $otherDentist = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
       $otherDentist->assignRole('dentist');
    $this->actingAs($otherDentist);

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id))
        ->assertSessionHas('error');
    $this->assertDatabaseCount('treatments', 0);
});

// ---------------------------------------------------------------------------
// the appointment must be approved
// ---------------------------------------------------------------------------

it('refuses to treat a pending appointment that is not approved yet', function () {
    $pending = treatTestAppointment($this->patient, $this->user, $this->procedure, [
        'is_approve' => 'pending',
    ]);

    $response = $this->post(route('treatments.store', $pending), treatTestPayload($this->medication->id));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('treatments', 0);
});

it('refuses to treat a cancelled appointment', function () {
    $cancelled = treatTestAppointment($this->patient, $this->user, $this->procedure, [
        'status' => 'cancelled',
    ]);

    $this->post(route('treatments.store', $cancelled), treatTestPayload($this->medication->id));

    $this->assertDatabaseCount('treatments', 0);
});

it('does not open the treatment page for a pending appointment', function () {
    $pending = treatTestAppointment($this->patient, $this->user, $this->procedure, [
        'is_approve' => 'pending',
    ]);

    $response = $this->get(route('treatments.create', $pending));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('allows only one treatment per appointment', function () {
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id))
        ->assertSessionHasNoErrors();

    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));

    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'already has a treatment'));
    $this->assertDatabaseCount('treatments', 1);
});

// ---------------------------------------------------------------------------
// saving a treatment
// ---------------------------------------------------------------------------

it('saves the treatment with duration, follow-up and summary, then goes to the treatment list', function () {
    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('treatments.index'));
    $response->assertSessionHas('message', fn ($message) => str_starts_with($message, 'Successfully created treatment'));

    $this->assertDatabaseHas('treatments', [
        'appointment_id' => $this->appointment->id,
        'duration_minutes' => 30,
        'summary' => 'Extraction done, no complications',
    ]);
});

it('requires a duration, a follow-up date and a summary', function (string $field) {
    $payload = treatTestPayload($this->medication->id);
    unset($payload[$field]);

    $response = $this->post(route('treatments.store', $this->appointment), $payload);

    $response->assertSessionHasErrors($field);
    $this->assertDatabaseCount('treatments', 0);
})->with(['duration_minutes', 'follow_up_schedule', 'summary']);

it('accepts any number of prescriptions', function () {
    $second = Medication::create(['name' => 'Ibuprofen', 'strength' => '400 mg']);
    $third = Medication::create(['name' => 'Paracetamol', 'strength' => '500 mg']);

    $prescriptions = [
        ['medication_id' => $this->medication->id, 'dosage' => '500 mg', 'frequency' => 'Three times a day', 'duration_days' => 5],
        ['medication_id' => $second->id, 'dosage' => '400 mg', 'frequency' => 'As needed', 'duration_days' => 3],
        ['medication_id' => $third->id, 'dosage' => '500 mg', 'frequency' => 'Every 6 hours', 'duration_days' => 2],
    ];

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [], [
        'prescriptionlist' => $prescriptions,
    ]))->assertSessionHasNoErrors();

    $treatment = Treatment::where('appointment_id', $this->appointment->id)->firstOrFail();
    expect($treatment->perscriptions()->count())->toBe(3);
});

it('rejects a prescription for a medication that does not exist', function () {
    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload(999999));

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount('treatments', 0);
});

// ---------------------------------------------------------------------------
// items and stock
// ---------------------------------------------------------------------------

it('reduces the stock by the quantity of items used', function () {
    $syringe = treatTestItem('Dental Syringe');
    treatTestStock($syringe, 10);

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 4],
    ]))->assertSessionHasNoErrors();

    expect($syringe->fresh()->current_stock)->toBe(6.0);
});

it('reduces the stock of every item used, for any number of items', function () {
    $syringe = treatTestItem('Dental Syringe');
    $mirror = treatTestItem('Dental Mirror');
    $tip = treatTestItem('Suction Tip');
    treatTestStock($syringe, 10);
    treatTestStock($mirror, 5);
    treatTestStock($tip, 8);

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 2],
        ['item_id' => $mirror->id, 'quantity' => 1],
        ['item_id' => $tip->id, 'quantity' => 3],
    ]))->assertSessionHasNoErrors();

    expect($syringe->fresh()->current_stock)->toBe(8.0);
    expect($mirror->fresh()->current_stock)->toBe(4.0);
    expect($tip->fresh()->current_stock)->toBe(5.0);
});

it('refuses an item quantity above the stock on hand and saves nothing', function () {
    $syringe = treatTestItem('Dental Syringe');
    treatTestStock($syringe, 3);

    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 5],
    ]));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('treatments', 0);
    expect($syringe->fresh()->current_stock)->toBe(3.0);
});

it('still saves when an item falls below its minimum, and shows a warning', function () {
    $syringe = treatTestItem('Dental Syringe', 10, 3);
    treatTestStock($syringe, 12);

    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 5],
    ]));

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('message', fn ($message) => str_contains($message, 'Note:'));
    $this->assertDatabaseCount('treatments', 1);
    expect($syringe->fresh()->current_stock)->toBe(7.0);
});

it('still saves when an item falls to its critical level, and shows a warning', function () {
    $syringe = treatTestItem('Dental Syringe', 10, 3);
    treatTestStock($syringe, 5);

    $response = $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 3],
    ]));

    $response->assertSessionHas('message', fn ($message) => str_contains($message, 'Note:'));
    $this->assertDatabaseCount('treatments', 1);
    expect($syringe->fresh()->current_stock)->toBe(2.0);
});

// ---------------------------------------------------------------------------
// editing a treatment
// ---------------------------------------------------------------------------

it('opens the edit page with the saved items and prescriptions', function () {
    $syringe = treatTestItem('Dental Syringe');
    treatTestStock($syringe, 10);
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 4],
    ]));
    $treatment = Treatment::firstOrFail();

    $response = $this->get(route('treatments.edit', $treatment));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Treatments/Edit')
        ->where('treatment.id', $treatment->id)
        ->where('treatment.itemList.0.item_id', $syringe->id)
        ->where('treatment.itemList.0.quantity', 4)
        ->has('treatment.prescriptionlist', 1)
    );
});

it('gives back the old item usage and takes the new usage on update', function () {
    $syringe = treatTestItem('Dental Syringe');
    treatTestStock($syringe, 10);
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 4],
    ]));
    $treatment = Treatment::firstOrFail();

    $this->put(route('treatments.update', $treatment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 7],
    ]))->assertSessionHasNoErrors();

    expect($syringe->fresh()->current_stock)->toBe(3.0);
});

it('refuses an update that uses more of an item than is in stock', function () {
    $syringe = treatTestItem('Dental Syringe');
    treatTestStock($syringe, 10);
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 4],
    ]));
    $treatment = Treatment::firstOrFail();

    $response = $this->put(route('treatments.update', $treatment), treatTestPayload($this->medication->id, [
        ['item_id' => $syringe->id, 'quantity' => 50],
    ]));

    $response->assertSessionHas('error');
    expect($syringe->fresh()->current_stock)->toBe(6.0);
});

// ---------------------------------------------------------------------------
// the treatment list
// ---------------------------------------------------------------------------

it('lists treatments with the patient name', function () {
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));

    $response = $this->get(route('treatments.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Treatments/Index')
        ->has('treatments.data', 1)
        ->where('treatments.data.0.patient_name', 'Juan Cruz')
    );
});

it('finds treatments by patient name', function () {
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));

    $response = $this->get(route('treatments.index', ['search' => 'Juan']));

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('treatments.data', 1));
});

// ---------------------------------------------------------------------------
// the summary email
// ---------------------------------------------------------------------------

it('emails the treatment summary to the patient', function () {
    Mail::fake();

    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));

    Mail::assertSent(TreatmentSummaryMail::class, fn ($mail) => $mail->hasTo('juan.treat@example.com'));
});

// ---------------------------------------------------------------------------
// delete is not offered
// ---------------------------------------------------------------------------

it('does not offer deleting a treatment', function () {
    $this->post(route('treatments.store', $this->appointment), treatTestPayload($this->medication->id));
    $treatment = Treatment::firstOrFail();

    $response = $this->delete(route('treatments.destroy', $treatment));

    $response->assertStatus(405);
    $this->assertDatabaseHas('treatments', ['id' => $treatment->id]);
});