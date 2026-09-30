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
            // Dashboard  // ADDED
            'dashboard.view',

            // Users
            'users.route',

            'users.view',
            'users.create',
            'users.update',
            'users.delete', // also used by users.deactivate route

            // Patients
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete', // also used by patients.deactivate route

            // Inventory (covers /inventories and /inventory_items routes)
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            // Procedures  // ADDED
            'procedures.view',
            'procedures.create',
            'procedures.update',
            'procedures.delete',

            // Medications  // ADDED
            'medications.view',
            'medications.create',
            'medications.update',
            'medications.delete',

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

            // Reports  // ADDED
            'reports.view',

            // Patient-specific
            'profile.view',
            'profile.update',
            'appointments.view_own',
            'treatments.view_own', // ADDED
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
            // Dashboard // ADDED
            'dashboard.view',

            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Reports // ADDED
            'reports.view',
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

            // Appointments // ADDED
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            // Treatments (Table 4: Staff records treatments) // ADDED
            'treatments.view',
            'treatments.create',
            'treatments.update',

            // Procedures & medications // ADDED
            'procedures.view',
            'procedures.create',
            'procedures.update',
            'procedures.delete',
            'medications.view',
            'medications.create',
            'medications.update',
            'medications.delete',

            // Reports // ADDED
            'reports.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Dentist Permissions
        |--------------------------------------------------------------------------
        */

        $dentist->syncPermissions([
            // Patients (add, update, view) // ADDED
            'patients.view',
            'patients.create',
            'patients.update',

            // Scheduling (view own schedule only) // CHANGED: was full CRUD
            'appointments.view',

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

            // Reference data (read-only) // ADDED
            'procedures.view',
            'medications.view',
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
            'appointments.create',  // ADDED: patients book their own appointments
            'treatments.view_own',  // ADDED: view own treatment summaries
        ]);
    }
}