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
