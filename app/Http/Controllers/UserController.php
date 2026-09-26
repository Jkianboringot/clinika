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
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
/**
 * =====================================================================
 * USER MANAGEMENT MODULE — Controller Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Manages system users — Admin, Staff, and Dentist accounts.
 *          Patient accounts are technically a "user" type too, but
 *          will live in a SEPARATE controller/file (noted here only
 *          for reference — see Patient module).
 * =====================================================================
 *
 * ACCESS / ROLES
 * ---------------
 * - Accessible by ADMIN and STAFF, but permissions differ by role:
 *   - ADMIN  : can create/edit/delete Dentist & Staff accounts.
 *   - STAFF  : cannot create Dentist or Staff — staff can only
 *              manage (create/edit/delete) PATIENT accounts.
 *
 * NATURE OF THIS MODULE
 * ----------------------
 * - Could be a simple Controller or a Service — not heavily decided yet.
 * - Requires HEAVY authorization checks on Create, given role sensitivity.
 *
 * DATA FIELDS
 * -----------
 * - name       : string, min 3 / max 75 chars
 * - email      : string, min 3 / max 75–150 chars (TBD)
 *                - must be UNIQUE (used for login — patients log in via email)
 *                - uniqueness check must be IGNORED on update (self-email)
 * - is_active  : enum -> active | deactivated
 * - password   : must be hashed before storage
 * - role       : assigned role (admin | dentist | staff | patient)
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI -> Route -> Controller -> Custom Request Form (validation) -> (Service?)
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. Creation restriction by role
 *    - STAFF cannot create Dentist or Staff accounts.
 *    - STAFF can only create/manage Patient accounts.
 *    - ADMIN can create/edit/delete, but only within their responsibility scope.
 *
 * 2. Role assignment safeguard
 *    - When assigning a role, system must NOT allow:
 *        - Staff being assigned "Admin" permission.
 *        - Dentist being assigned "Admin" or "Staff" permission.
 *    - [REMINDER] This is meant to guard against human error during
 *      role assignment (e.g. accidentally setting wrong role).
 *      Permissions themselves are already tied to role — this is
 *      more of a safety net than a core system responsibility.
 *
 * 3. Delete restrictions
 *    - ADMIN accounts can NEVER be deleted (not even by another admin).
 *    - No user can delete THEMSELVES — self-deletion is blocked for
 *      Admin, Dentist, Staff, and Patient alike.
 *    - Only ADMIN can delete other users (Dentist/Staff/Patient).
 *    - Must check existence/validity before delete.
 *
 * 4. Self-editing allowance
 *    - Dentist, Staff, and Patient CANNOT delete themselves,
 *      but CAN edit their own information via their profile.
 *
 * 5. Update safeguard
 *    - All edits go through Custom Request Form validation.
 *    - Email uniqueness check excludes the current user's own record.
 *
 * =====================================================================
 * REVIEW (implementation note, not part of the original spec above):
 * Rule 1 and rule 3 read as mildly contradictory once Patient accounts
 * are pulled out into their own controller — if Staff's only write
 * access is to Patients, and Patients aren't managed here, Staff ends
 * up with NO create/update/delete rights in this module at all (view
 * only). That's the reading this controller and UserPolicy are built
 * on. Flag me if Staff was actually meant to retain some write access
 * here.
 * =====================================================================
 *
 * FIX NOTE (role filter):
 * The frontend's index tabs only cover 'dentist' | 'staff' | 'patient'
 * (no Admin tab exists in Users/Index.tsx). Defaulting the role filter
 * to 'admin' when no ?role= is present meant a fresh page load showed
 * Admin accounts with no tab highlighted as active, and TAB_TITLES had
 * no 'admin' entry on the frontend. Default now matches the first tab.
 * =====================================================================
 */

class UserController extends Controller
{

    public function index(SearchRequest $request): Response
    {
        $this->authorize('users.view');

        $validated = $request->validated();

        // Default to 'dentist' — the first tab the frontend actually renders.
        // (Frontend TABS = dentist | staff | patient; there is no Admin tab.)
        $activeRole = $validated['role'] ?? 'dentist';

        abort_unless(
            in_array($activeRole, ['admin', 'dentist', 'staff', 'patient'], true),
            403
        );

        if ($activeRole === 'patient') {
            // Patients live in their own table/model (see Patient.php), not
            // in `users` with a "patient" role — so this branch queries
            // Patient directly. `full_name as name` aliases the column so
            // the frontend can read `row.name` the same way it does for
            // users, without needing to know the underlying column differs.
            $users = Patient::query()
                ->select(['id', 'full_name as name', 'email', 'contact_number', 'created_at'])
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

        $roleCounts = collect(['admin', 'dentist', 'staff'])
            ->mapWithKeys(
                fn($role) => [$role => User::role($role)->count()]
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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.delete');

        if ($user->hasRole('admin')) {
            abort(403, 'Admin accounts cannot be deleted.');
        }

        if ($user->id === Auth::id()) {
            abort(403, "You can't delete your own account.");
        }

        $user->delete();

        return redirect()->route('users.index')->with('message', 'User deleted successfully.');
    }

    private function assignableRoles(): array
    {
        return Auth::user()->hasRole('admin')
            ? ['admin', 'dentist', 'staff', 'patient']
            : ['dentist'];
    }

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