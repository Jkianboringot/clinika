<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;

class AppointmentPolicy extends ModulePolicy
{
    protected string $module = 'appointments';

    private function owns($user, $appointment): bool
    {
        return $user instanceof Patient
            && (int) $appointment->patient_id === (int) $user->id;
    }

    private function patientCan($user): bool
    {
        // The patient routes are guarded by appointments.view_own
        return $user instanceof Patient && $user->checkPermissionTo('appointments.view_own');
    }

    /** A treated or cancelled appointment is locked. */
    private function locked($appointment): bool
    {
        return $appointment->status === 'cancelled'
            || Treatment::where('appointment_id', $appointment->id)->exists();
    }

    public function viewAny($user): bool
    {
        return $this->staffCan($user, 'view') || $this->patientCan($user);
    }

    public function view($user, $appointment): bool
    {
        return $this->staffCan($user, 'view')
            || ($this->patientCan($user) && $this->owns($user, $appointment));
    }

    public function create($user): bool
    {
        return $this->staffCan($user, 'create') || $this->patientCan($user);
    }

    public function update($user, $appointment): bool
    {
        if ($this->locked($appointment)) {
            return false;
        }

        return $this->staffCan($user, 'update')
            || ($this->patientCan($user) && $this->owns($user, $appointment));
    }

    /** Cancel. */
    public function delete($user, $appointment): bool
    {
        if ($this->locked($appointment)) {
            return false;
        }

        return $this->staffCan($user, 'delete')
            || ($this->patientCan($user) && $this->owns($user, $appointment));
    }

    public function cancel($user, $appointment): bool
    {
        return $this->delete($user, $appointment);
    }

    /** Staff only. The approval routes use appointments.update. */
    public function approve($user, $appointment): bool
    {
        return $this->staffCan($user, 'update') && $appointment->status !== 'cancelled';
    }

    public function reject($user, $appointment): bool
    {
        return $this->staffCan($user, 'update') && $appointment->status !== 'cancelled';
    }

    public function viewQuestionnaire($user, $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}
