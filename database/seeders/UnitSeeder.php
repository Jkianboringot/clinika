<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed important inventory units (recommended for your system)
        Unit::create([
            'name' => 'Piece',
            'pieces_per_unit' => 1,
        ]);

        Unit::create([
            'name' => 'Pack (12 pcs)',
            'pieces_per_unit' => 12,
        ]);

        Unit::create([
            'name' => 'Pack (10 pcs)',
            'pieces_per_unit' => 10,
        ]);

        Unit::create([
            'name' => 'Box (50 pcs)',
            'pieces_per_unit' => 50,
        ]);

        // Optional: generate extra random units (for testing only)
        // Unit::factory()->count(5)->create();
    }
}