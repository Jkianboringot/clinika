<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Users
            'users.route',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Patients
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',

            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            // Scheduling / Appointments
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            // Follow-ups
            'followups.view',
            'followups.create',
            'followups.update',
            'followups.delete',

            // Treatment Details
            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',

            // Patient-specific
            'profile.view',
            'profile.update',
            'appointments.view_own',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $patient = Role::firstOrCreate([
            'name' => 'patient',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $staff = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);

        $dentist = Role::firstOrCreate([
            'name' => 'dentist',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Admin Permissions
        |--------------------------------------------------------------------------
        */

        $admin->syncPermissions([
            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Staff Permissions
        |--------------------------------------------------------------------------
        */

        $staff->syncPermissions([
            // Patients
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',

            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Dentist Permissions
        |--------------------------------------------------------------------------
        */

        $dentist->syncPermissions([
            // Scheduling
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            // Follow-ups
            'followups.view',
            'followups.create',
            'followups.update',
            'followups.delete',

            // Treatment details
            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Patient Permissions
        |--------------------------------------------------------------------------
        */

        $patient->syncPermissions([
            'profile.view',
            'profile.update',
            'appointments.view_own',
        ]);
    }
}