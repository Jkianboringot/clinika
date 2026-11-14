<?php

// Run from the Laravel project root: php create-policies.php

$files = [];

$files['ModulePolicy'] = <<<'EOT'
<?php

namespace App\Policies;

use App\Models\User;

/**
 * Base for the staff-side modules. A staff user passes when they hold
 * "<module>.<action>", the same permission names your route middleware uses.
 * Patients never pass here; modules that patients may touch override the methods.
 */
abstract class ModulePolicy
{
    protected string $module;

    protected function staffCan($user, string $action): bool
    {
        return $user instanceof User
            && $user->checkPermissionTo("{$this->module}.{$action}");
    }

    public function viewAny($user): bool
    {
        return $this->staffCan($user, 'view');
    }

    public function view($user, $model): bool
    {
        return $this->staffCan($user, 'view');
    }

    public function create($user): bool
    {
        return $this->staffCan($user, 'create');
    }

    public function update($user, $model): bool
    {
        return $this->staffCan($user, 'update');
    }

    public function delete($user, $model): bool
    {
        return $this->staffCan($user, 'delete');
    }
}
EOT;

$files['ProcedurePolicy'] = <<<'EOT'
<?php

namespace App\Policies;

class ProcedurePolicy extends ModulePolicy
{
    protected string $module = 'procedures';
}
EOT;

$files['MedicationPolicy'] = <<<'EOT'
<?php

namespace App\Policies;

class MedicationPolicy extends ModulePolicy
{
    protected string $module = 'medications';
}
EOT;

$files['InventoryPolicy'] = <<<'EOT'
<?php

namespace App\Policies;

class InventoryPolicy extends ModulePolicy
{
    protected string $module = 'inventory';
}
EOT;

$files['InventoryItemPolicy'] = <<<'EOT'
<?php

namespace App\Policies;

class InventoryItemPolicy extends ModulePolicy
{
    protected string $module = 'inventory';
}
EOT;

$files['UserPolicy'] = <<<'EOT'
<?php

namespace App\Policies;

class UserPolicy extends ModulePolicy
{
    protected string $module = 'users';

    public function activate($user, $target): bool
    {
        return $this->staffCan($user, 'update');
    }

    /** Uses users.delete (as the route does), and nobody can deactivate themselves. */
    public function deactivate($user, $target): bool
    {
        return $this->staffCan($user, 'delete') && ! $user->is($target);
    }
}
EOT;

$files['PatientPolicy'] = <<<'EOT'
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
EOT;

$files['AppointmentPolicy'] = <<<'EOT'
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
EOT;

$files['TreatmentPolicy'] = <<<'EOT'
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
EOT;

$files['MedicalQuestionnairePolicy'] = <<<'EOT'
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
EOT;

$dir = __DIR__ . '/app/Policies';

if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($files as $name => $code) {
    file_put_contents("{$dir}/{$name}.php", $code . "\n");
    echo "created app/Policies/{$name}.php\n";
}

echo "\nDone. Laravel auto-discovers these (Model -> ModelPolicy), no registration needed.\n";