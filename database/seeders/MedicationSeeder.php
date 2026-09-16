<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        if (Medication::count() === 0) {
            Medication::factory()->count(15)->create();
        }
    }
}
