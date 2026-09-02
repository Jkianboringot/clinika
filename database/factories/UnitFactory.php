<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Piece',
                'Pack',
                'Box',
                'Bundle',
                'Carton',
            ]),
            'pieces_per_unit' => $this->faker->numberBetween(5,20),
            'created_at' => now(),
            'updated_at' => now(),

        ];
    }
}