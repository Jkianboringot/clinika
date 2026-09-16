<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'minimum_stock' => fake()->numberBetween(5, 50),
        ];
    }
}
