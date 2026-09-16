<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProcedureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true) . ' Procedure',
            'description' => fake()->optional()->sentence(),
            'duration' => fake()->numberBetween(15, 120),
            'price' => fake()->randomFloat(2, 500, 15000),
            'is_active' => true,
        ];
    }
}
