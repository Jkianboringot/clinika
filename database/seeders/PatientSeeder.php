<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        if (Patient::count() === 0) {
            Patient::factory()->count(50)->create();
        }
    }
}
