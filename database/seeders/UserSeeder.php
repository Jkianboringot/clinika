<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('123'),
            ]
        );
        $admin->assignRole('admin');

        $staff = User::firstOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('123'),
            ]
        );
        $staff->assignRole('staff');

        $dentist = User::firstOrCreate(
            ['email' => 'dentist@gmail.com'],
            [
                'name' => 'Dentist User',
                'password' => Hash::make('123'),
            ]
        );
        $dentist->assignRole('dentist');

        // Patient account (patient guard). Adjust the columns below to match
        // your patients table: add any NOT NULL columns it requires.
        $patient = Patient::firstOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'full_name' => 'Patient User',
                'date_of_birth' => '2000-01-01',
                'gender' => GenderEnum::MALE->value,
                'contact_number' => '09123456789',
                'address' => 'Naga City, Camarines Sur',
                'medical_history' => null,
                'allergies' => null,
                'is_active' => IsActiveEnum::ACTIVE->value,
                'emergency_contact_number' => null,
                'emergency_contact_name' => null,
                'password' => Hash::make('123'),
            ]
        );
        $patient->assignRole('patient'); // resolves to the "patient" guard via $guard_name
    }
}