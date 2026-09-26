<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        if (Procedure::count() === 0) {
            Procedure::factory()->count(50)->create();
        }
    }
}
