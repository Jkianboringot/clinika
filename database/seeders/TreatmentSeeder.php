<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\Medication;
use App\Models\InventoryItem;
use App\Models\Treatment;
use App\Models\TreatmentPrescriptionDetails;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointmentIds = Appointment::pluck('id');
        $doctorIds = User::role('admin')->pluck('id');

        if ($doctorIds->isEmpty()) {
            $doctorIds = User::pluck('id');
        }

        $medicationIds = Medication::pluck('id');
        $inventoryItemIds = InventoryItem::pluck('id');

        if ($appointmentIds->isEmpty() || $doctorIds->isEmpty()) {
            $this->command->warn(
                'TreatmentSeeder skipped: make sure Appointments and Users are seeded first.'
            );
            return;
        }

        // One treatment per appointment (logically), so don't seed more
        // treatments than there are appointments available.
        $count = min(50, $appointmentIds->count());
        $chosenAppointmentIds = $appointmentIds->shuffle()->take($count);

        foreach ($chosenAppointmentIds as $appointmentId) {
            DB::transaction(function () use ($appointmentId, $doctorIds, $medicationIds, $inventoryItemIds) {
                $treatment = Treatment::create([
                    'user_id' => $doctorIds->random(),
                    'appointment_id' => $appointmentId,
                    'follow_up_schedule' => fake()->boolean(50)
                        ? fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d')
                        : null,
                    'duration_minutes' => fake()->boolean(80) ? fake()->numberBetween(15, 120) : null,
                    'summary' => fake()->boolean(80) ? fake()->sentence(12) : null,
                ]);

                // Prescription details (0-3 per treatment)
                if ($medicationIds->isNotEmpty()) {
                    $prescriptionCount = rand(0, 3);
                    $pickedMedicationIds = $medicationIds->random(
                        min($prescriptionCount, $medicationIds->count())
                    );
                    $pickedMedicationIds = $pickedMedicationIds instanceof \Illuminate\Support\Collection
                        ? $pickedMedicationIds
                        : collect($prescriptionCount > 0 ? [$pickedMedicationIds] : []);

                    foreach ($pickedMedicationIds as $medicationId) {
                        TreatmentPrescriptionDetails::create([
                            'treatment_id' => $treatment->id,
                            'medication_id' => $medicationId,
                            'dosage' => fake()->randomElement(['250mg', '500mg', '1 tablet', '5ml']),
                            'frequency' => fake()->randomElement(['Once daily', 'Twice daily', 'Every 8 hours', 'As needed']),
                            'duration_days' => fake()->numberBetween(3, 14),
                            'dentist_note' => fake()->boolean(30) ? fake()->sentence(8) : null,
                        ]);
                    }
                }

                // Inventory items used during treatment (1-3 per treatment)
                if ($inventoryItemIds->isNotEmpty()) {
                    $itemCount = rand(1, 3);
                    $pickedItemIds = $inventoryItemIds->random(
                        min($itemCount, $inventoryItemIds->count())
                    );
                    $pickedItemIds = $pickedItemIds instanceof \Illuminate\Support\Collection
                        ? $pickedItemIds
                        : collect([$pickedItemIds]);

                    foreach ($pickedItemIds as $itemId) {
                        $treatment->treatmentInventories()->create([
                            'inventory_item_id' => $itemId,
                            'quantity' => fake()->randomFloat(2, 1, 10),
                        ]);
                    }
                }

                // Mark the underlying appointment as completed, matching
                // what TreatmentService::treatmentStore() does at runtime.
                Appointment::where('id', $appointmentId)
                    ->update(['status' => AppointmentStatusEnum::Completed->value]);
            });
        }

        $this->command->info("Seeded {$count} treatments with prescriptions and inventory usage.");
    }
}