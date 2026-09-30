<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RolePermissionSeeder::class, 

             UserSeeder::class,           
            // PatientSeeder::class,
            ProcedureSeeder::class,
            MedicationSeeder::class,
            // AppointmentSeeder::class,
            InventoryItemSeeder::class,
            //   InventoryStockAdditionSeeder::class,
//   TreatmentSeeder::class, 
            ]
        );
        User::factory(10)->create();

    
    }
}
