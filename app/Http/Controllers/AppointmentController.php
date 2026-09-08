<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;
/**
 * =====================================================================
 * APPOINTMENT MODULE — Controller Notes
 * =====================================================================
 * Type   : Appiontment — Backend Logic
 * Purpose: Handles creation, update, and deletion of patient appointments
 *          by clinic staff. Patients cannot manage appointments directly.
 * =====================================================================
 *
 * ACCESS
 * ------
 * - Only STAFF can create/manage appointments.
 * - Used to schedule a patient's visit (e.g. extraction, cleaning).
 *
 * DATA FIELDS
 * -----------
 * - patient_id
 * - user_id          (staff who created it)
 * - appointment_time
 * - status           : cancelled | rescheduled | completed | follow_up
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI (sends validated input)
 *     -> Route
 *       -> Controller (this file)
 *         -> Custom Request (validation)
 *           -> Appointment Service
 *             -> returns result + error handling
 *
 *   NOTE: Controller stays thin. Business logic lives in the Service layer.
 *
 * VALIDATION RULES
 * -----------------
 * - Appointment date cannot be before today.
 * - Appointment time should fall within reasonable clinic hours
 *   (not strictly enforced yet — flexible for now).
 * - Status must match the defined enum above.
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. No duplicate same-day appointments
 *    - A patient cannot book twice in one day.
 *    - Additional visits must be booked on a different day.
 *
 * 2. [REMINDER] Daily booking limit
 *    - Idea: cap appointments per day based on available time slots.
 *    - NOT required yet — not specified, decision deferred.
 *
 * 3. Reschedule approval rule
 *    - [REMINDER] Check phone notes for more detail.
 *    - Current approach: patient contacts staff -> staff manually updates
 *      the appointment -> staff's action itself serves as approval.
 *      (No separate approval flag needed.)
 *
 * DELETE RULES
 * -------------
 * - Allowed only if:
 *     - status is "cancelled", OR
 *     - it's the same day and still pending/unchanged.
 * - NOT allowed if status is "completed" or "follow_up"
 *   (preserve historical data).
 * - Must warn user: "This will delete the data permanently —
 *   you are responsible."
 *
 * UPDATE RULES
 * -------------
 * - Same flow as Create.
 * - Must check if appointment exists first -> return 404 if not.
 *
 * =====================================================================
 */

class AppointmentController extends Controller
{

    
    public function create()
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('Appointments/Create', []);
    }

    public function store(AppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $request->validated();

        Appointment::create($request->all());
        return redirect()->route('appointments.index')->with('message', 'Appointment Created Successfully');
    }

    public function destroy(Request $request)
    {
        $appointment = Appointment::findOrFail($request->id);
        $this->authorize('delete', $appointment);

        $appointment->deleteOrFail();
        return redirect()->route('appointments.index')->with('message', 'Appointment Deleted Successfully');
    }

    public function edit(Request $request)
    {
        $p = Appointment::findOrFail($request->id);
        $this->authorize('view', $p);

        return Inertia::render('Appointments/Edit', ['appointments' => $p]);
    }

    public function update(AppointmentRequest $request)
    {
        $p = Appointment::findOrFail($request->id);
        $this->authorize('update', $p);

        $request->validated();
        $p->update($request->all());

        return redirect()->route('appointments.index')->with('message', 'Appointment Updated Successfully');
    }

    public function index(SearchRequest $request)
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validated();
        $appointments = Appointment::query()
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['search']),
        ]);
    }
}