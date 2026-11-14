<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Treatment;
use App\Models\TreatmentPrescriptionDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPrescriptionDetails>
 */
class TreatmentPrescriptionDetailsFactory extends Factory
{
    protected $model = TreatmentPrescriptionDetails::class;

    public function definition(): array
    {
        return [
            'treatment_id' => Treatment::factory(),
            'medication_id' => Medication::factory(),
            'dosage' => fake()->randomElement(['250mg', '500mg', '1 tablet', '2 tablets', '5ml']),
            'frequency' => fake()->randomElement([
                'Once a day',
                'Twice a day',
                'Every 8 hours',
                'Every 12 hours',
                'After meals',
            ]),
            'duration_days' => fake()->numberBetween(3, 14),
            'dentist_note' => fake()->optional(0.5)->sentence(),
        ];
    }
}