<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ingredients')->insert([
            [
                'name' => 'Beef Patty',
                'threshold' => 10,
                'selling_price' => 50,
                'cost' => 30,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Burger Bun',
                'threshold' => 20,
                'selling_price' => 15,
                'cost' => 8,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cheese Slice',
                'threshold' => 15,
                'selling_price' => 10,
                'cost' => 6,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lettuce',
                'threshold' => 30,
                'selling_price' => 5,
                'cost' => 2,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tomato',
                'threshold' => 25,
                'selling_price' => 8,
                'cost' => 3,
                'unit_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}