<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\SearchRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;


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
 */


class UserController extends Controller
{
    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', []);
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $request->validated();

        User::create($request->all());
        return redirect()->route('users.index')->with('message', 'User Created Successfully');
    }

    public function destroy(Request $request)
    {
        $user = User::findOrFail($request->id);
        $this->authorize('delete', $user);

        $user->deleteOrFail();
        return redirect()->route('users.index')->with('message', 'User Deleted Successfully');
    }

    public function edit(Request $request)
    {
        $p = User::findOrFail($request->id);
        $this->authorize('view', $p);

        return Inertia::render('Users/Edit', ['users' => $p]);
    }

    public function update(UserRequest $request)
    {
        $p = User::findOrFail($request->id);
        $this->authorize('update', $p);

        $request->validated();
        $p->update($request->all());

        return redirect()->route('users.index')->with('message', 'User Updated Successfully');
    }

    public function index(SearchRequest $request)
    {
        $this->authorize('viewAny', User::class);

        $request->validated();
        $users = User::query()
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }
}