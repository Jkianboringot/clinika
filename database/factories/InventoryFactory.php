<?php

namespace Database\Factories;

use App\Enums\InOutType;
use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'inventory_type' => fake()->randomElement([
                InOutType::In->value,
                InOutType::Out->value,
            ]),

            'encoder_id' => User::inRandomOrder()->value('id'),

            'branch_id' => Branch::inRandomOrder()->value('id'),

            'stock_movement_type' => fake()->randomElement([
                StockMovementType::Transfer->value,
            ]),
        ];
    }
}