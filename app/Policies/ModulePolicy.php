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
