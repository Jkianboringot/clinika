<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prefer staff/doctor-role users if roles are set up; fall back to any user.
        $staffIds = User::role('admin')->pluck('id');

        

        $patientIds = Patient::pluck('id');
        $procedureIds = Procedure::pluck('id');

        if ($staffIds->isEmpty() || $patientIds->isEmpty() || $procedureIds->isEmpty()) {
            $this->command->warn(
                'AppointmentSeeder skipped: make sure Users, Patients, and Procedures are seeded first.'
            );
            return;
        }

        $statuses = array_map(fn ($case) => $case->value, AppointmentStatusEnum::cases());

        $appointments = [];

        for ($i = 0; $i < 50; $i++) {
            $appointments[] = [
                'staff_id' => $staffIds->random(),
                'patient_id' => $patientIds->random(),
                'procedure_id' => $procedureIds->random(),
                'description' => fake()->boolean(70) ? fake()->sentence(8) : null,
                'appointment_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
                'appointment_time' => fake()->time('H:i:s'),
                'status' => fake()->randomElement($statuses),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks to avoid a single huge query / batch event overhead.
        collect($appointments)->chunk(20)->each(function ($chunk) {
            Appointment::insert($chunk->toArray());
        });

        $this->command->info('Seeded 50 appointments.');
    }
}