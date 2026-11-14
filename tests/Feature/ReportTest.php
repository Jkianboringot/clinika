<?php

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsActiveEnum;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentInventory;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create(['is_active' => IsActiveEnum::ACTIVE->value]);
    $this->user->assignRole('staff'); // staff has procedures.view/create/update/delete

    $this->actingAs($this->user);
});

/**
 * Creates a patient with a known name and birth date.
 */
function reportPatient(string $first, string $last, string $dob = '1990-01-01', array $overrides = []): Patient
{
    return Patient::factory()->create(array_merge([
        'first_name' => $first,
        'middle_name' => null,
        'last_name' => $last,
        'date_of_birth' => $dob,
        'is_active' => IsActiveEnum::ACTIVE->value,
    ], $overrides));
}

/**
 * Creates an appointment for a patient on a given date. Uses the first status case by default.
 */
function reportAppointment(Patient $patient, string $date, ?string $status = null): Appointment
{
    return Appointment::factory()->create([
        'patient_id' => $patient->id,
        'appointment_date' => $date,
        'appointment_time' => '09:00:00',
        'status' => $status ?? AppointmentStatusEnum::cases()[0]->value,
    ]);
}

/**
 * Creates a treatment linked to an appointment.
 */
function reportTreatment(Appointment $appointment, int $minutes = 30): Treatment
{
    return Treatment::factory()->create([
        'appointment_id' => $appointment->id,
        'duration_minutes' => $minutes,
    ]);
}

/**
 * Adds stock the same way the app does, through InventoryService::inventoryStore().
 */
function reportStockAdd(InventoryItem $item, float $quantity): void
{
    app(InventoryService::class)->inventoryStore([
        'itemList' => [['item_id' => $item->id, 'quantity' => $quantity]],
    ]);
}

/**
 * Records item usage in a treatment. treatment_id is not mass assignable.
 */
function reportTreatmentUse(InventoryItem $item, float $quantity, Treatment $treatment): void
{
    $row = new TreatmentInventory([
        'inventory_item_id' => $item->id,
        'quantity' => $quantity,
    ]);
    $row->treatment_id = $treatment->id;
    $row->save();
}

// ---------------------------------------------------------------------------
// type and filter validation
// ---------------------------------------------------------------------------

it('defaults to the patients report when no type is given', function () {
    $response = $this->get('/reports');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Report/Index')
        ->where('type', 'patients')
    );
});

it('returns 404 for an unknown report type', function () {
    $this->get('/reports?type=not-a-report')->assertNotFound();
});

it('returns 404 for an unknown report type on export', function () {
    $this->get('/reports/export?type=not-a-report')->assertNotFound();
});

it('rejects a to date earlier than the from date', function () {
    $response = $this->get('/reports?type=appointments&from=2026-03-10&to=2026-01-01');

    $response->assertSessionHasErrors('to');
});

it('rejects an invalid date filter', function () {
    $response = $this->get('/reports?type=appointments&from=not-a-date');

    $response->assertSessionHasErrors('from');
});

// ---------------------------------------------------------------------------
// patients report
// ---------------------------------------------------------------------------

it('lists patients with age, visits and last visit', function () {
    $patient = reportPatient('Maria', 'Santos', '2000-06-15');
    reportAppointment($patient, '2026-01-10');
    reportAppointment($patient, '2026-03-10');

    $response = $this->get('/reports?type=patients');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.name', 'Maria Santos')
        ->where('rows.0.visits', 2)
        ->where('rows.0.last_visit', '2026-03-10')
    );
});

it('counts only visits that match the date and status filters', function () {
    $patient = reportPatient('Maria', 'Santos');
    $statusA = AppointmentStatusEnum::cases()[0]->value;
    $statusB = AppointmentStatusEnum::cases()[1]->value;
    reportAppointment($patient, '2026-01-10', $statusA);
    reportAppointment($patient, '2026-03-10', $statusB);

    $response = $this->get('/reports?type=patients&status=' . $statusA);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('rows.0.visits', 1)
    );
});

it('still lists a patient with zero visits', function () {
    reportPatient('No', 'Visits');

    $response = $this->get('/reports?type=patients');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.visits', 0)
        ->where('rows.0.last_visit', '—')
    );
});

it('searches patients by name', function () {
    reportPatient('Maria', 'Santos');
    reportPatient('Juan', 'Dela Cruz');

    $response = $this->get('/reports?type=patients&search=Santos');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.name', 'Maria Santos')
    );
});

it('summarises total patients, active patients and total visits', function () {
    $active = reportPatient('Maria', 'Santos');
    reportPatient('Juan', 'Dela Cruz', '1990-01-01', ['is_active' => IsActiveEnum::DEACTIVED->value]);
    reportAppointment($active, '2026-01-10');

    $response = $this->get('/reports?type=patients');

    $summary = $response->viewData('page')['props']['summary'];
    expect($summary[0])->toMatchArray(['label' => 'Total Patients', 'value' => 2]);
    expect($summary[1])->toMatchArray(['label' => 'Active Patients', 'value' => 1]);
    expect($summary[2])->toMatchArray(['label' => 'Total Visits', 'value' => 1]);
});

// ---------------------------------------------------------------------------
// appointments report
// ---------------------------------------------------------------------------

it('lists appointments ordered by date and time', function () {
    $patient = reportPatient('Maria', 'Santos');
    reportAppointment($patient, '2026-03-10');
    reportAppointment($patient, '2026-01-10');

    $response = $this->get('/reports?type=appointments');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 2)
        ->where('rows.0.date', '2026-01-10')
        ->where('rows.1.date', '2026-03-10')
    );
});

it('filters appointments by date range', function () {
    $patient = reportPatient('Maria', 'Santos');
    reportAppointment($patient, '2026-01-10');
    reportAppointment($patient, '2026-03-10');

    $response = $this->get('/reports?type=appointments&from=2026-02-01&to=2026-12-31');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.date', '2026-03-10')
    );
});

it('filters appointments by status', function () {
    $patient = reportPatient('Maria', 'Santos');
    $statusA = AppointmentStatusEnum::cases()[0]->value;
    $statusB = AppointmentStatusEnum::cases()[1]->value;
    reportAppointment($patient, '2026-01-10', $statusA);
    reportAppointment($patient, '2026-01-11', $statusB);

    $response = $this->get('/reports?type=appointments&status=' . $statusB);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.date', '2026-01-11')
    );
});

it('searches appointments by patient name', function () {
    $maria = reportPatient('Maria', 'Santos');
    $juan = reportPatient('Juan', 'Dela Cruz');
    reportAppointment($maria, '2026-01-10');
    reportAppointment($juan, '2026-01-11');

    $response = $this->get('/reports?type=appointments&search=Dela Cruz');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.patient', 'Juan Dela Cruz')
    );
});

it('counts appointments in total and by status in the summary', function () {
    $patient = reportPatient('Maria', 'Santos');
    $statusA = AppointmentStatusEnum::cases()[0]->value;
    reportAppointment($patient, '2026-01-10', $statusA);
    reportAppointment($patient, '2026-01-11', $statusA);

    $response = $this->get('/reports?type=appointments');

    $summary = $response->viewData('page')['props']['summary'];
    expect($summary[0])->toMatchArray(['label' => 'Total', 'value' => 2]);
});

// ---------------------------------------------------------------------------
// treatments report
// ---------------------------------------------------------------------------

it('lists treatments with duration and items used', function () {
    $patient = reportPatient('Maria', 'Santos');
    $appointment = reportAppointment($patient, '2026-01-10');
    $treatment = reportTreatment($appointment, 45);
    $item = InventoryItem::create(['item_name' => 'Dental Syringe']);
    reportStockAdd($item, 10);
    reportTreatmentUse($item, 2, $treatment);

    $response = $this->get('/reports?type=treatments');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.patient', 'Maria Santos')
        ->where('rows.0.items', 'Dental Syringe × 2')
    );

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['duration'])->toBe(45);
});

it('sums treatment minutes in the summary', function () {
    $patient = reportPatient('Maria', 'Santos');
    $appointment = reportAppointment($patient, '2026-01-10');
    reportTreatment($appointment, 30);
    reportTreatment($appointment, 45);

    $response = $this->get('/reports?type=treatments');

    $summary = $response->viewData('page')['props']['summary'];
    expect($summary[0])->toMatchArray(['label' => 'Treatments', 'value' => 2]);
    expect($summary[1])->toMatchArray(['label' => 'Total Minutes', 'value' => 75]);
});

it('filters treatments by appointment date range', function () {
    $patient = reportPatient('Maria', 'Santos');
    reportTreatment(reportAppointment($patient, '2026-01-10'));
    reportTreatment(reportAppointment($patient, '2026-03-10'));

    $response = $this->get('/reports?type=treatments&from=2026-02-01');

    $response->assertInertia(fn (AssertableInertia $page) => $page->has('rows', 1));
});

// ---------------------------------------------------------------------------
// inventory report
// ---------------------------------------------------------------------------

it('shows remaining stock as the all-time total added minus total used', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Syringe']);
    reportStockAdd($item, 10);
    reportStockAdd($item, 5);
    $treatment = Treatment::factory()->create();
    reportTreatmentUse($item, 4, $treatment);

    $response = $this->get('/reports?type=inventory');

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['remaining'])->toBe(11.0);
    expect($props['rows'][0]['added'])->toBe(15.0);
    expect($props['rows'][0]['used'])->toBe(4.0);
});

it('never shows negative remaining stock', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Syringe']);
    reportStockAdd($item, 3);
    reportTreatmentUse($item, 8, Treatment::factory()->create());

    $response = $this->get('/reports?type=inventory');

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['remaining'])->toBe(0.0);
});

it('keeps remaining stock all-time while added and used follow the date range', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Syringe']);
    reportStockAdd($item, 10);

    $response = $this->get('/reports?type=inventory&from=2099-01-01');

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['added'])->toBe(0.0);
    expect($props['rows'][0]['remaining'])->toBe(10.0);
});

it('flags an item at or below its minimum as LOW STOCK and counts it', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Syringe', 'minimum_stock' => 10]);
    reportStockAdd($item, 5);

    $response = $this->get('/reports?type=inventory');

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['status'])->toBe('LOW STOCK');
    expect($props['summary'][1])->toMatchArray(['label' => 'Low Stock', 'value' => 1]);
});

it('marks an item with no minimum as OK and never low', function () {
    $item = InventoryItem::create(['item_name' => 'Dental Mirror']);
    reportStockAdd($item, 1);

    $response = $this->get('/reports?type=inventory');

    $props = $response->viewData('page')['props'];
    expect($props['rows'][0]['status'])->toBe('OK');
    expect($props['rows'][0]['minimum'])->toBe('—');
});

it('searches inventory items by name', function () {
    InventoryItem::create(['item_name' => 'Dental Syringe']);
    InventoryItem::create(['item_name' => 'Dental Mirror']);

    $response = $this->get('/reports?type=inventory&search=Syringe');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('rows', 1)
        ->where('rows.0.item', 'Dental Syringe')
    );
});

// ---------------------------------------------------------------------------
// CSV export
// ---------------------------------------------------------------------------

it('exports a CSV file with a UTF-8 BOM and the report headers', function () {
    reportPatient('Maria', 'Santos');

    $response = $this->get('/reports/export?type=patients');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('patients-report-');

    $content = $response->streamedContent();
    expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF");
    expect($content)->toContain('Patient');
    expect($content)->toContain('Maria Santos');
});

it('exports the same rows the screen shows for the chosen filters', function () {
    $patient = reportPatient('Maria', 'Santos');
    reportAppointment($patient, '2026-01-10');
    reportAppointment($patient, '2026-03-10');

    $content = $this->get('/reports/export?type=appointments&from=2026-02-01')->streamedContent();

    expect(substr_count($content, '2026-03-10'))->toBe(1);
    expect($content)->not->toContain('2026-01-10');
});

it('neutralises a cell that starts with a formula character', function () {
    reportPatient('Maria', 'Santos', '1990-01-01', ['contact_number' => '=1+1']);

    $content = $this->get('/reports/export?type=patients')->streamedContent();

    expect($content)->toContain("'=1+1");
    expect($content)->not->toContain(',=1+1');
});

it('does not export the internal id column', function () {
    reportPatient('Maria', 'Santos');

    $content = $this->get('/reports/export?type=patients')->streamedContent();
    $header = strtok(substr($content, 3), "\n");

    expect($header)->not->toContain('"id"');
    expect($header)->not->toContain('ID');
});

// ---------------------------------------------------------------------------
// per-patient report
// ---------------------------------------------------------------------------

it('shows the patient report with personal details, appointments and treatments', function () {
    $patient = reportPatient('Maria', 'Santos', '2000-06-15');
    $appointment = reportAppointment($patient, '2026-01-10');
    reportTreatment($appointment, 30);

    $response = $this->get('/reports/patients/' . $patient->id);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Report/Patient')
        ->where('patient.full_name', 'Maria Santos')
        ->has('appointments', 1)
        ->has('treatments', 1)
    );
});

it('includes only the chosen patient\'s appointments and treatments', function () {
    $maria = reportPatient('Maria', 'Santos');
    $juan = reportPatient('Juan', 'Dela Cruz');
    reportAppointment($maria, '2026-01-10');
    reportAppointment($juan, '2026-01-11');
    reportAppointment($juan, '2026-01-12');

    $response = $this->get('/reports/patients/' . $maria->id);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('appointments', 1)
        ->where('appointments.0.date', 'Jan 10, 2026')
    );
});

it('shows a patient with no history with empty lists and zero counts', function () {
    $patient = reportPatient('Maria', 'Santos');

    $response = $this->get('/reports/patients/' . $patient->id);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('appointments', 0)
        ->has('treatments', 0)
    );

    $summary = $response->viewData('page')['props']['summary'];
    expect($summary[0])->toMatchArray(['label' => 'Appointments', 'value' => 0]);
    expect($summary[2])->toMatchArray(['label' => 'Last Visit', 'value' => '—']);
});

it('calculates the patient age from the date of birth', function () {
    $dob = now()->subYears(30)->subMonth()->format('Y-m-d');
    $patient = reportPatient('Maria', 'Santos', $dob);

    $response = $this->get('/reports/patients/' . $patient->id);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('patient.age', 30)
    );
});

it('returns 404 for a patient that does not exist', function () {
    $this->get('/reports/patients/999999')->assertNotFound();
});