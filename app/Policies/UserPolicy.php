<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

/**
 * =====================================================================
 * USER POLICY — reading notes
 * =====================================================================
 * INSIGHT: the controller's docblock says this whole module only manages
 * Admin/Staff/Dentist accounts — Patient accounts live in a SEPARATE
 * controller. That one line resolves what otherwise looks like a
 * contradiction in the spec:
 *   - "ACCESS / ROLES" says Staff "can only manage (create/edit/delete)
 *     PATIENT accounts" — but Patient management doesn't happen here.
 *   - "Delete restrictions" says "Only ADMIN can delete other users
 *     (Dentist/Staff/Patient)".
 * Read together: within THIS module, Staff has no create/update/delete
 * rights at all — their write access to Patients is a different
 * controller entirely. Staff's role here is read-only (viewAny/view).
 * REVIEW: flag me if that's not what you meant — it's the interpretation
 * this policy is built on.
 * =====================================================================
 */
class UserPolicy
{
    /**
     * Both Admin and Staff can see the list (docblock: "Accessible by
     * ADMIN and STAFF, but permissions differ by role").
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN->value, RoleEnum::STAFF->value], true);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return true; // self-view, e.g. loading your own edit page
        }

        return in_array($user->role, [RoleEnum::ADMIN->value, RoleEnum::STAFF->value], true);
    }

    /**
     * Create is Admin-only here. Staff can't create Dentist or Staff
     * (docblock), and Patient creation isn't this controller's job —
     * so there's nothing left in this module for Staff to create.
     */
    public function create(User $user): bool
    {
        return $user->role === RoleEnum::ADMIN->value;
    }

    /**
     * SECURITY: safety-net check on top of create() — guards against a
     * human picking the wrong option in a role dropdown (docblock point 2).
     * Not a substitute for create() above; call both.
     */
    public function createRole(User $user, string $targetRole): bool
    {
        if ($user->role !== RoleEnum::ADMIN->value) {
            return false;
        }

        // REVIEW: docblock never says whether Admin can create another
        // Admin ("only within their responsibility scope" is vague), and
        // Patient belongs to the other controller. Excluding both here —
        // widen this if Admin creation should be possible through this form.
        return in_array($targetRole, [RoleEnum::DENTIST->value, RoleEnum::STAFF->value], true);
    }

    /**
     * Self-editing is always allowed (docblock point 4: Dentist/Staff/
     * Patient can edit their own info). Editing someone ELSE's account
     * here is Admin-only, per the same scoping note as create().
     */
    public function update(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return true;
        }

        return $user->role === RoleEnum::ADMIN->value;
    }

    /**
     * SECURITY: safety-net for role changes during an update — same
     * intent as createRole(), applied to point 2's "Dentist being
     * assigned Admin or Staff" and "Staff being assigned Admin" cases.
     * $model is the account being edited (its CURRENT role), $newRole is
     * what the form is trying to set it to.
     */
    public function updateRole(User $user, User $model, string $newRole): bool
    {
        if ($user->role !== RoleEnum::ADMIN->value) {
            return false;
        }

        if ($model->role === RoleEnum::STAFF->value && $newRole === RoleEnum::ADMIN->value) {
            return false;
        }

        if ($model->role === RoleEnum::DENTIST->value
            && in_array($newRole, [RoleEnum::ADMIN->value, RoleEnum::STAFF->value], true)) {
            return false;
        }

        return true;
    }

    /**
     * Delete restrictions, straight from the docblock:
     *  - nobody deletes themselves, any role
     *  - Admin accounts are never deletable, by anyone
     *  - only Admin can delete other users
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        if ($model->role === RoleEnum::ADMIN->value) {
            return false;
        }

        return $user->role === RoleEnum::ADMIN->value;
    }
}
