<?php

namespace App\Http\Controllers;

use App\Enums\IsApproveEnum;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lists appointments that are NOT approved (pending, pending reschedule,
 * rejected) and lets staff approve or reject them.
 *
 * The normal appointments list only shows approved ones.
 */
class AppointmentApprovalController extends Controller
{
       public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }
    /** Columns needed so Patient's full_name accessor can be built. */
    private const PATIENT_NAME_COLUMNS = ['id', 'first_name', 'middle_name', 'last_name'];

    public function index(Request $request): Response
    {
        $tab = $request->query('tab') === 'rejected' ? 'rejected' : 'pending';

        $statuses = $tab === 'rejected'
            ? [IsApproveEnum::Reject->value]
            : $this->pendingValues();

        $appointments = Appointment::query()
            ->with([
                'patient:' . implode(',', self::PATIENT_NAME_COLUMNS),
                'procedure:id,name',
                'dentist:id,name',
            ])
            ->whereIn('is_approve', $statuses)
            // oldest request first for the pending queue, newest first for rejected
            ->orderBy('created_at', $tab === 'rejected' ? 'desc' : 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Approvals', [
            'appointments' => $appointments,
            'tab' => $tab,
            'counts' => [
                'pending' => Appointment::whereIn('is_approve', $this->pendingValues())->count(),
                'rejected' => Appointment::where('is_approve', IsApproveEnum::Reject->value)->count(),
            ],
        ]);
    }

    /** Pending, pending reschedule, or previously rejected -> approved. */
    public function approve(Appointment $appointment): RedirectResponse
    {
        try {
            // The state check is part of the UPDATE, so it only succeeds if the
            // appointment is currently waiting for a decision (or was rejected).
            $updated = Appointment::whereKey($appointment->id)
                ->whereIn('is_approve', [
                    ...$this->pendingValues(),
                    IsApproveEnum::Reject->value,
                ])
                ->update(['is_approve' => IsApproveEnum::Approve->value]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to approve appointment.');
        }

        if ($updated === 0) {
            return back()->with('error', 'This appointment is not waiting for approval.');
        }

        return back()->with('message', 'Appointment approved.');
    }

    /** Pending or pending reschedule -> rejected. */
    public function reject(Appointment $appointment): RedirectResponse
    {
        try {
            $updated = Appointment::whereKey($appointment->id)
                ->whereIn('is_approve', $this->pendingValues())
                ->update(['is_approve' => IsApproveEnum::Reject->value]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to reject appointment.');
        }

        if ($updated === 0) {
            return back()->with('error', 'Only pending appointments can be rejected.');
        }

        return back()->with('message', 'Appointment rejected.');
    }

    /** @return string[] */
    private function pendingValues(): array
    {
        return [
            IsApproveEnum::Pending->value,
            IsApproveEnum::PendingReScheduled->value,
        ];
    }
}