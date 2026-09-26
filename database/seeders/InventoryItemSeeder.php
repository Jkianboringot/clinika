<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        if (InventoryItem::count() === 0) {
            InventoryItem::factory()->count(50)->create();
        }
    }
}
