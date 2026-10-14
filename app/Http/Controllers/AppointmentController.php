<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsActiveEnum;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * =====================================================================
 * APPOINTMENT MODULE — Controller Notes
 * =====================================================================
 * Only STAFF create/manage appointments. Controller stays thin.
 *
 * STATUS LIFECYCLE (all automatic — the client never sets status)
 * ---------------------------------------------------------------
 * - Create                       -> scheduled
 * - Date or time changed (edit)  -> rescheduled
 * - Treatment created            -> completed   (done in TreatmentService)
 * - Treatment deleted            -> cancelled   (done in TreatmentService)
 * - "Delete" on the UI           -> cancelled   (row is NEVER deleted)
 *
 * TREATMENT GUARD
 * ---------------
 * An appointment that already has a treatment is proof a service was
 * delivered (income), so it can't be cancelled, "deleted" or edited.
 * The check runs inside a transaction with the row locked, and
 * TreatmentService locks the same row first — so a treatment can't sneak
 * in between the check and the status change.
 * =====================================================================
 */

class AppointmentController extends Controller
{
    /** Changing any of these on update flips the status to "rescheduled". */
    private const SCHEDULE_FIELDS = ['appointment_date', 'appointment_time'];

    /** Columns needed so Patient's full_name accessor can be built. */
    private const PATIENT_NAME_COLUMNS = ['id', 'first_name', 'middle_name', 'last_name'];

    public function index(SearchRequest $request): Response
    {
        // $this->authorize('viewAny', Appointment::class);

        $request->validated();

        $appointments = Appointment::query()
            ->with([
                'staff:id,name',
                'patient:' . implode(',', self::PATIENT_NAME_COLUMNS),
                'procedure:id,name',
            ])
            ->when(
                $request->string('search')->trim(),
                fn ($query, $search) => $query->whereHas(
                    'patient',
                    fn ($q) => $q->whereRaw(
                        "CONCAT_WS(' ', first_name, middle_name, last_name) like ?",
                        ["%{$search}%"]
                    )
                )
            )
            ->orderBy('created_at', 'desc')
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
            'patients' => $this->activePatients(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        try {
            Appointment::create([
                // status is never taken from the request
                ...Arr::except($request->validated(), ['status']),
                'staff_id' => auth()->id(),
                'status' => AppointmentStatusEnum::Scheduled->value,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('appointments.index')
                ->with('error', 'Failed to create appointment.');
        }

        return redirect()->route('appointments.index')->with('message', 'Appointment created successfully.');
    }

    public function edit(Appointment $appointment): Response
    {
        // $this->authorize('view', $appointment);

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'patients' => $this->activePatients(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        try {
            $refusal = DB::transaction(function () use ($request, $appointment) {
                $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                if ($this->hasTreatment($locked)) {
                    return 'This appointment already has a treatment and can no longer be edited.';
                }

                $locked->fill([
                    ...Arr::except($request->validated(), ['status']),
                    'staff_id' => auth()->id(),
                ]);

                // Date or time moved -> rescheduled. Anything else keeps its status.
                if ($locked->isDirty(self::SCHEDULE_FIELDS)) {
                    $locked->status = AppointmentStatusEnum::Rescheduled->value;
                }

                $locked->save();

                return null;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('appointments.index')
                ->with('error', 'Failed to update appointment.');
        }

        if ($refusal) {
            return redirect()->route('appointments.index')->with('error', $refusal);
        }

        return redirect()->route('appointments.index')->with('message', 'Appointment updated successfully.');
    }

    /**
     * "Delete" = cancel. The row is kept for history; only the status changes.
     * Blocked entirely if a treatment is attached.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        // $this->authorize('delete', $appointment);

        try {
            $result = DB::transaction(function () use ($appointment) {
                $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                // Proof a service was delivered — hiding it would hide income.
                if ($this->hasTreatment($locked)) {
                    return ['error', 'This appointment has a treatment assigned and cannot be cancelled.'];
                }

                if ($this->statusValue($locked) === AppointmentStatusEnum::Cancelled->value) {
                    return ['message', 'Appointment is already cancelled.'];
                }

                $locked->status = AppointmentStatusEnum::Cancelled->value;
                $locked->save();

                return ['message', 'Appointment cancelled successfully.'];
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('appointments.index')->with('error', 'Failed to cancel appointment.');
        }

        return redirect()->route('appointments.index')->with($result[0], $result[1]);
    }

    /** Active patients for the dropdowns, sorted by name. */
    private function activePatients()
    {
        return Patient::where('is_active', IsActiveEnum::ACTIVE->value)
            ->select(self::PATIENT_NAME_COLUMNS)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /** A treatment row OR a completed status both mean "service delivered". */
    private function hasTreatment(Appointment $appointment): bool
    {
        return $appointment->treatment()->exists()
            || $this->statusValue($appointment) === AppointmentStatusEnum::Completed->value;
    }

    /** Works whether or not the model casts status to the enum. */
    private function statusValue(Appointment $appointment): ?string
    {
        $status = $appointment->status;

        return $status instanceof \BackedEnum ? $status->value : $status;
    }
}