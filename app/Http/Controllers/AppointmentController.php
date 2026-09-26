<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
    public function index(SearchRequest $request): Response
    {
        // $this->authorize('viewAny', Appointment::class);

        $request->validated();

        $appointments = Appointment::query()
            ->with(['staff:id,name', 'patient:id,full_name', 'procedure:id,name'])
            ->when(
                $request->string('search')->trim(),
                fn($query, $search) =>
                    $query->whereHas('patient', fn($q) => $q->where('full_name', 'like', "{$search}%"))
            )
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {

        return Inertia::render('Appointments/Create', [
            'patients' => Patient::select('id', 'full_name')->orderBy('full_name')->get(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {

        Appointment::create($request->validated());

        return redirect()->route('appointments.index')->with('message', 'Appointment created successfully.');
    }

    public function edit(Appointment $appointment): Response
    {
        // $this->authorize('view', $appointment);

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'patients' => Patient::select('id', 'full_name')->orderBy('full_name')->get(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {

        $appointment->update($request->validated());

        return redirect()->route('appointments.index')->with('message', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        // $this->authorize('delete', $appointment);

        // its good that this is not allowed to be delete becuase it is ilegal to allow delete
        //  of something that prove service has been completely delivered that is hiding income
        if ($appointment->treatment()->exists()) {
            return redirect()
                ->route('appointments.index')
                ->with('error', 'This appointment has treatment assigned and cannot be deleted.');
        }
        try {
            $appointment->deleteOrFail();

        } catch (\Throwable $e) {
            return redirect()->route('appointments.index')->with('error', 'Failed to delete, already has with treatment');
        }

        return redirect()->route('appointments.index')->with('message', 'Appointment deleted successfully.');
    }





}