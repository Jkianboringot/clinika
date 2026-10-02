<?php

namespace App\Http\Controllers;

use App\Enums\IsActiveEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\UserRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * =====================================================================
 * USER MANAGEMENT MODULE — Controller Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Manages system users — Admin, Staff, and Dentist accounts.
 *          Patient accounts live in a SEPARATE controller/file
 *          (see Patient module).
 * =====================================================================
 *
 * ACCESS / ROLES
 * ---------------
 * - Accessible by ADMIN and STAFF, but permissions differ by role:
 *   - ADMIN  : can create/edit/deactivate Dentist & Staff accounts.
 *   - STAFF  : view only in this module (Patient management lives
 *              in the Patient module).
 *
 * DATA FIELDS
 * -----------
 * - name       : string, min 3 / max 75 chars
 * - email      : unique (used for login); ignored on self-update
 * - is_active  : enum -> active | inactive
 * - password   : hashed before storage
 * - role       : admin | dentist | staff
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI -> Route -> Controller -> Custom Request Form (validation)
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. Role assignment safeguard
 *    - Only roles returned by assignableRoles() can be assigned.
 *    - Admin accounts cannot be reassigned to another role.
 *
 * 2. Delete = DEACTIVATE (soft)
 *    - Users are never removed from the database. "Delete" sets
 *      is_active to INACTIVE, invalidates remember-me token, and
 *      wipes their sessions so they are logged out immediately.
 *    - Deactivated users are hidden from the index and tab counts.
 *    - Deactivated users cannot log in (see LoginRequest check and
 *      EnsureUserIsActive middleware).
 *    - ADMIN accounts can NEVER be deactivated.
 *    - No user can deactivate THEMSELVES.
 *    - Related records (appointments, inventory, treatment) stay
 *      intact, so no relation checks are needed anymore.
 *
 * 3. Self-editing
 *    - Users edit their own info via their profile page, not here.
 *
 * 4. Update safeguard
 *    - All edits go through Custom Request Form validation.
 *    - Email uniqueness check excludes the current user's own record.
 *
 * NOTE (role filter):
 * The frontend's index tabs only cover 'dentist' | 'staff' | 'patient'
 * (no Admin tab), so the default role filter is 'dentist'.
 * =====================================================================
 */

class UserController extends Controller
{
    public function index(SearchRequest $request): Response
    {
        $this->authorize('users.view');

        $validated = $request->validated();

        // Default to 'dentist' — the first tab the frontend actually renders.
        $activeRole = $validated['role'] ?? 'dentist';

        abort_unless(
            in_array($activeRole, ['admin', 'dentist', 'staff', 'patient'], true),
            403
        );

        if ($activeRole === 'patient') {
            // Patients live in their own table/model (see Patient.php).
            // `full_name as name` aliases the column so the frontend can
            // read `row.name` the same way it does for users.
            $users = Patient::query()
                ->select(['id', 'full_name as name', 'email', 'contact_number', 'created_at', 'is_active'])
                ->when(
                    $validated['search'] ?? null,
                    fn($query, $search) =>
                        $query->where('full_name', 'like', "%{$search}%")
                )
                ->orderBy('created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            $users = User::query()
                ->role($activeRole)
                ->when(
                    $validated['search'] ?? null,
                    fn($query, $search) =>
                        $query->where('name', 'like', "%{$search}%")
                )
                ->orderBy('created_at')
                ->paginate(15)
                ->withQueryString();
        }

        $roleCounts = collect(['dentist', 'staff'])
            ->mapWithKeys(
                fn($role) => [
                    $role => User::role($role)
                        ->count(),
                ]
            )
            ->merge(['patient' => Patient::count()]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'role' => $activeRole,
            ],
            'roleCounts' => $roleCounts,
            'activeRole' => $activeRole,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('users.create');

        return Inertia::render('Users/Create', [
            'assignableRoles' => $this->assignableRoles(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('users.create');

        $validated = $request->validated();
        $this->guardRoleAssignment($validated['role']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => IsActiveEnum::ACTIVE->value,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('message', 'User created successfully.');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('users.view');

        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'is_active']) + [
                'role' => $user->getRoleNames()->first(),
            ],
            'assignableRoles' => $this->assignableRoles(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('users.update');

        if ($user->id === Auth::id()) {
            abort(403, 'Use your profile page to edit your own account.');
        }

        $validated = $request->validated();
        $this->guardRoleAssignment($validated['role'], $user);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('message', 'User updated successfully.');
    }

    /**
     * "Delete" is a soft deactivation: the row stays, is_active flips to
     * INACTIVE, and the user is logged out everywhere.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.delete');

        if ($user->hasRole('admin')) {
            abort(403, 'Admin accounts cannot be deactivated.');
        }

        if ($user->id === Auth::id()) {
            abort(403, "You can't deactivate your own account.");
        }

        $user->update([
            'is_active' => IsActiveEnum::DEACTIVED->value,
            'remember_token' => Str::random(60), // invalidate "remember me" cookies
        ]);
        // Kill active sessions (requires SESSION_DRIVER=database)
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()->route('users.index')->with('message', 'User deactivated successfully.');
    }

    /**
     * Reactivate a previously deactivated user. Only works if the user is
     * currently INACTIVE; the check is part of the UPDATE query, so two
     * concurrent requests can't both pass it.
     */
    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.update');

        if ($user->id === Auth::id()) {
            abort(403, "You can't change the status of your own account.");
        }

        $updated = User::whereKey($user->id)
            ->where('is_active', IsActiveEnum::DEACTIVED->value)
            ->update(['is_active' => IsActiveEnum::ACTIVE->value]);

        if ($updated === 0) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Only deactivated users can be activated.');
        }

        return redirect()->route('users.index')->with('message', 'User activated successfully.');
    }
    private function assignableRoles(): array
    {
        return Auth::user()->hasRole('admin')
            ? [ 'dentist', 'staff']
            : ['dentist'];
    }

    // Ensures only roles in assignableRoles() can be assigned, and admins
    // can't be moved to another role.
    private function guardRoleAssignment(string $role, ?User $target = null): void
    {
        if (!in_array($role, $this->assignableRoles(), true)) {
            abort(403, 'You are not allowed to assign that role.');
        }

        if ($target?->hasRole('admin') && $role !== 'admin') {
            abort(403, 'Admin accounts cannot be reassigned to another role.');
        }
    }
}