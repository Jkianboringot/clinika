<?php

namespace Database\Seeders;

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
    }
}