<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' ' . fake()->randomElement(['500mg', '250mg', '10ml']),
            'generic_name' => fake()->word(),
            'dosage_form' => fake()->randomElement(['Tablet', 'Capsule', 'Syrup', 'Injection']),
            'strength' => fake()->randomElement(['250mg', '500mg', '1g', '5ml']),
        ];
    }
}
