<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class MedicalQuestionnairePolicy
{
    private function owns($user, $questionnaire): bool
    {
        return $user instanceof Patient
            && (int) $questionnaire->patient_id === (int) $user->id;
    }

    /** Staff read it through patients.view, a patient reads their own. */
    public function view($user, $questionnaire): bool
    {
        return ($user instanceof User && $user->checkPermissionTo('patients.view'))
            || $this->owns($user, $questionnaire);
    }

    /** Only the patient changes their own answers. Staff have a read-only view. */
    public function update($user, $questionnaire): bool
    {
        return $this->owns($user, $questionnaire)
            && $user->checkPermissionTo('profile.update');
    }
}
