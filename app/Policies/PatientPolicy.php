<?php

namespace App\Policies;

use App\Models\Patient;

class PatientPolicy extends ModulePolicy
{
    protected string $module = 'patients';

    private function isSelf($user, $patient): bool
    {
        return $user instanceof Patient && $user->is($patient);
    }

    public function view($user, $patient): bool
    {
        return $this->staffCan($user, 'view') || $this->isSelf($user, $patient);
    }

    /** Staff with patients.update, or a patient editing their own profile. */
    public function update($user, $patient): bool
    {
        return $this->staffCan($user, 'update')
            || ($this->isSelf($user, $patient) && $user->checkPermissionTo('profile.update'));
    }

    /** The routes use patients.delete for both deactivate and activate. */
    public function deactivate($user, $patient): bool
    {
        return $this->staffCan($user, 'delete');
    }

    public function activate($user, $patient): bool
    {
        return $this->staffCan($user, 'delete');
    }

    public function viewQuestionnaire($user, $patient): bool
    {
        return $this->view($user, $patient);
    }
}
