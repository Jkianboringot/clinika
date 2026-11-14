<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Patient;

class TreatmentPolicy extends ModulePolicy
{
    protected string $module = 'treatments';

    public function viewAny($user): bool
    {
        return $this->staffCan($user, 'view')
            || ($user instanceof Patient && $user->checkPermissionTo('treatments.view_own'));
    }

    public function view($user, $treatment): bool
    {
        if ($this->staffCan($user, 'view')) {
            return true;
        }

        return $user instanceof Patient
            && $user->checkPermissionTo('treatments.view_own')
            && Appointment::whereKey($treatment->appointment_id)
                ->where('patient_id', $user->id)
                ->exists();
    }
}
