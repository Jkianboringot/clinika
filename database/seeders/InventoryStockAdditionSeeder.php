<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStockAddition;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryStockAdditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffIds = User::pluck('id');
        $itemIds = InventoryItem::pluck('id');

        if ($staffIds->isEmpty() || $itemIds->isEmpty()) {
            $this->command->warn(
                'InventoryStockAdditionSeeder skipped: make sure Users and InventoryItems are seeded first.'
            );
            return;
        }

        $inventories = [];

        for ($i = 0; $i < 50; $i++) {
            $inventories[] = [
                'staff_id' => $staffIds->random(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        collect($inventories)->chunk(20)->each(function ($chunk) {
            Inventory::insert($chunk->toArray());
        });

        // Now attach 1-4 stock addition line items to each inventory entry.
        $inventoryIds = Inventory::pluck('id');
        $stockAdditions = [];

        foreach ($inventoryIds as $inventoryId) {
            $lineCount = rand(1, 4);
            $pickedItemIds = $itemIds->random(min($lineCount, $itemIds->count()));
            $pickedItemIds = $pickedItemIds instanceof \Illuminate\Support\Collection
                ? $pickedItemIds
                : collect([$pickedItemIds]);

            foreach ($pickedItemIds as $itemId) {
                $stockAdditions[] = [
                    'inventory_id' => $inventoryId,
                    'inventory_item_id' => $itemId,
                    'quantity' => fake()->randomFloat(2, 1, 200),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        collect($stockAdditions)->chunk(50)->each(function ($chunk) {
            InventoryStockAddition::insert($chunk->toArray());
        });

        $this->command->info('Seeded 50 inventory entries with stock addition line items.');
    }
}