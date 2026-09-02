<?php

namespace Database\Factories;

use App\Enums\Shift;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'branch_id' => 1,
            'inventory_id' => 1,

            'shift' => fake()->randomElement([
                Shift::Opening->value,
                Shift::Closing->value,
            ]),

            'cash_amount' => fake()->randomFloat(2, 0, 10000),
            'gcash_amount' => fake()->randomFloat(2, 0, 10000),
        ];
    }
}