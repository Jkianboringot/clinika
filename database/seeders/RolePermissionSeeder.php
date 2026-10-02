<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | WEB guard (User model: admin, staff, dentist)
        |--------------------------------------------------------------------------
        */

        $webPermissions = [
            'admin_dashboard.view',
            'staff_dashboard.view',
            'dentist_dashboard.view',
            'patient_dashboard.view',

            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',      // used by users.deactivate route

            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',   // used by patients.deactivate route

            'inventory.view',    // covers /inventories and /inventory_items
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            'procedures.view',
            'procedures.create',
            'procedures.update',
            'procedures.delete',

            'medications.view',
            'medications.create',
            'medications.update',
            'medications.delete',

            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            'followups.view',
            'followups.create',
            'followups.update',
            'followups.delete',

            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',

            'reports.view',
        ];

        foreach ($webPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $dentist = Role::firstOrCreate(['name' => 'dentist', 'guard_name' => 'web']);

        $admin->syncPermissions([
            'admin_dashboard.view',

            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'reports.view',
        ]);

        $staff->syncPermissions([
            'staff_dashboard.view',

            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',

            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',

            'treatments.view',
            // 'treatments.create',
            // 'treatments.update',
            // NOTE: no treatments.delete -> staff can't hit treatments.destroy

            'procedures.view',
            'procedures.create',
            'procedures.update',
            'procedures.delete',

            'medications.view',
            'medications.create',
            'medications.update',
            'medications.delete',

            'reports.view',
        ]);

        $dentist->syncPermissions([
             'dentist_dashboard.view',

            'patients.view',
            // 'patients.create',
            // 'patients.update',

            'appointments.view',

            'followups.view',
            'followups.create',
            'followups.update',
            'followups.delete',

            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',

            'procedures.view',
            'medications.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | PATIENT guard (Patient model)
        |--------------------------------------------------------------------------
        */

        $patientPermissions = [
                        'patient_dashboard.view',
            'profile.view',
            'profile.update',
            'appointments.view_own',
            'appointments.create',
            'treatments.view_own',
        ];

        foreach ($patientPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'patient']);
        }

        $patient = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'patient']);
        $patient->syncPermissions($patientPermissions);
    }
}