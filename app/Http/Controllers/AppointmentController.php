<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsActiveEnum;
use App\Enums\IsApproveEnum;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Appointment;
use App\Models\MedicalQuestionnaire;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use App\Services\DentistAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * =====================================================================
 * APPOINTMENT MODULE — Controller Notes
 * =====================================================================
 * Staff and patient bookings share the same rules (DentistAvailability):
 * working hours, booked slots, past times and the dentist's daily limit.
 *
 * STATUS LIFECYCLE (all automatic — the client never sets status)
 * ---------------------------------------------------------------
 * - Create                        -> scheduled
 * - Date, time or dentist changed -> rescheduled
 * - Treatment created             -> completed   (done in TreatmentService)
 * - Treatment deleted             -> cancelled   (done in TreatmentService)
 * - "Delete" on the UI            -> cancelled   (row is NEVER deleted)
 *
 * TREATMENT GUARD
 * ---------------
 * An appointment with a treatment can't be cancelled or edited. The check
 * runs inside a transaction with the row locked.
 *
 * QUESTIONNAIRE ON EDIT
 * ---------------------
 * Saved only when the page sends a "questionnaire" block, inside the same
 * locked transaction as the appointment.
 * =====================================================================
 */

class AppointmentController extends Controller
{
    /** Columns needed so Patient's full_name accessor can be built. */
    private const PATIENT_NAME_COLUMNS = ['id', 'first_name', 'middle_name', 'last_name'];

    public function __construct(private DentistAvailability $availability) {}

    public function index(SearchRequest $request): Response
    {
        // $this->authorize('viewAny', Appointment::class);

        $request->validated();

        $appointments = Appointment::query()
            ->with([
                'staff:id,name',
                'patient:' . implode(',', self::PATIENT_NAME_COLUMNS),
                'procedure:id,name',
                'dentist:id,name',
            ])
            ->where('is_approve', IsApproveEnum::Approve->value)
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

    public function create(): Response|RedirectResponse
    {
        // The questionnaire comes first. Without it in this session, go fill it in.
        if (! session()->has('pending_questionnaire')) {
            return redirect()->route('appointments.questionnaire');
        }

        return Inertia::render('Appointments/Create', [
            'patients' => $this->activePatients(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
            'dentists' => $this->dentists(),
        ]);
    }

    /** JSON: status for every day of one month for one dentist (available | full | unavailable | past). */
    public function dayStatuses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'dentist_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);

        return response()->json([
            'days' => $this->availability->monthStatuses(
                $data['month'],
                (int) $data['dentist_id'],
                $data['appointment_id'] ?? null,
            ),
        ]);
    }

    /** JSON: free "HH:mm" times for one dentist on one day. Pass appointment_id when editing so its own slot stays open. */
    public function openSlots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'dentist_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);

        $dentist = User::findOrFail($data['dentist_id']);

        return response()->json([
            'times' => $this->availability->freeTimes(
                $dentist,
                $data['date'],
                $data['appointment_id'] ?? null,
            ),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data) {
                // Lock the dentist first so two bookings can't both take the last slot.
                $dentist = User::whereKey($data['dentist_id'])->lockForUpdate()->firstOrFail();

                $conflict = $this->availability->conflict(
                    $dentist,
                    $data['appointment_date'],
                    $data['appointment_time'],
                );

                if ($conflict) {
                    return back()->withInput()->withErrors([
                        $conflict['field'] => $conflict['message'],
                    ]);
                }

                $patientId = (int) $data['patient_id'];
                $pending = session('pending_questionnaire');

                if ($pending) {
                    $patient = Patient::findOrFail($patientId);
                    $answers = $pending['answers'];

                    $womenKeys = collect(MedicalQuestionnaire::sections()['women'])->pluck('key')->all();

                    if (MedicalQuestionnaire::showsWomenSection($patient)) {
                        if (count(array_diff($womenKeys, array_keys($answers))) > 0) {
                            return redirect()
                                ->route('appointments.questionnaire')
                                ->with('error', 'This patient is female. Please answer the Women questions too.');
                        }
                    } else {
                        $answers = Arr::except($answers, $womenKeys);
                    }

                    MedicalQuestionnaire::updateOrCreate(
                        ['patient_id' => $patientId],
                        [
                            'answers' => $answers,
                            'conditions' => $pending['conditions'],
                            'agreed' => $pending['agreed'],
                        ],
                    );
                } elseif (! MedicalQuestionnaire::where('patient_id', $patientId)->exists()) {
                    return redirect()
                        ->route('appointments.questionnaire')
                        ->with('error', 'Fill in or skip the medical questionnaire first.');
                }

                Appointment::create([
                    ...Arr::except($data, ['status']),
                    'staff_id' => auth()->id(),
                    'status' => AppointmentStatusEnum::Scheduled->value,
                    'is_approve' => auth()->id()
                        ? IsApproveEnum::Approve->value
                        : IsApproveEnum::Pending->value,
                ]);

                session()->forget('pending_questionnaire');

                return redirect()
                    ->route('appointments.index')
                    ->with('message', 'Appointment created successfully.');
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('appointments.index')
                ->with('error', 'Failed to create appointment.');
        }
    }

    public function edit(Appointment $appointment): Response
    {
        // $this->authorize('view', $appointment);

        $questionnaire = MedicalQuestionnaire::where('patient_id', $appointment->patient_id)->first();

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'patients' => $this->activePatients(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
            'dentists' => $this->dentists(),
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($appointment->patient),
            'questionnaire' => $questionnaire ? [
                'answers' => $questionnaire->answers,
                'conditions' => $questionnaire->conditions,
            ] : null,
        ]);
    }
/** Staff: read-only questionnaire of the appointment's patient. */
public function showQuestionnaire(Appointment $appointment): Response
{
    $patient = $appointment->patient;

    $questionnaire = MedicalQuestionnaire::where('patient_id', $patient->id)->first();

    return Inertia::render('Appointments/Questionnaire', [
        'patient' => [
            'id' => $patient->id,
            'full_name' => $patient->full_name,
        ],
        'sections' => MedicalQuestionnaire::sections(),
        'isFemale' => MedicalQuestionnaire::showsWomenSection($patient),
        'questionnaire' => $questionnaire ? [
            'answers' => $questionnaire->answers,
            'conditions' => $questionnaire->conditions,
            'updated_at' => $questionnaire->updated_at?->format('M j, Y g:i A'),
        ] : null,
    ]);
}
    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        // Validated OUTSIDE the try so errors show on the form instead of being swallowed.
        $questionnaire = $this->validatedQuestionnaire($request, $appointment);
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data, $appointment, $questionnaire) {
                $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                if ($this->hasTreatment($locked)) {
                    return redirect()
                        ->route('appointments.index')
                        ->with('error', 'This appointment already has a treatment and can no longer be edited.');
                }

                $scheduleChanged = $this->scheduleChanged($locked, $data);
                $dentistChanged = (int) $locked->dentist_id !== (int) $data['dentist_id'];

                // Only re-check the slot when the date, time or dentist actually moved.
                if ($scheduleChanged || $dentistChanged) {
                    $dentist = User::whereKey($data['dentist_id'])->lockForUpdate()->firstOrFail();

                    $conflict = $this->availability->conflict(
                        $dentist,
                        $data['appointment_date'],
                        $data['appointment_time'],
                        $appointment->id,
                    );

                    if ($conflict) {
                        return back()->withInput()->withErrors([
                            $conflict['field'] => $conflict['message'],
                        ]);
                    }
                }

                $locked->fill([
                    ...Arr::except($data, ['status']),
                    'staff_id' => auth()->id(),
                ]);

                if ($scheduleChanged || $dentistChanged) {
                    $locked->status = AppointmentStatusEnum::Rescheduled->value;
                    $locked->is_approve = auth()->id()
                        ? IsApproveEnum::Approve->value
                        : IsApproveEnum::PendingReScheduled->value;
                }

                $locked->save();

                if ($questionnaire) {
                    MedicalQuestionnaire::updateOrCreate(
                        ['patient_id' => $appointment->patient_id],
                        [
                            'answers' => collect($questionnaire['answers'])
                                ->map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN))
                                ->all(),
                            'conditions' => array_values(array_unique($questionnaire['conditions'] ?? [])),
                            'agreed' => true,
                        ],
                    );
                }

                return redirect()
                    ->route('appointments.index')
                    ->with('message', 'Appointment updated successfully.');
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('appointments.index')
                ->with('error', 'Failed to update appointment.');
        }
    }

    /**
     * "Delete" = cancel. The row is kept for history; only the status changes.
     * Blocked entirely if a treatment is attached. Cancelled rows drop out of
     * DentistAvailability, so the slot and the day's count reopen automatically.
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

    /**
     * True only when the date or the HH:mm time differs from the saved row.
     * Normalizes both sides so "09:00" and "09:00:00", or "2026-11-02" and
     * "2026-11-02 00:00:00", count as the same value.
     */
    private function scheduleChanged(Appointment $appointment, array $data): bool
    {
        $oldDate = Carbon::parse($appointment->appointment_date)->toDateString();
        $newDate = Carbon::parse($data['appointment_date'])->toDateString();

        $oldTime = substr((string) $appointment->appointment_time, 0, 5);
        $newTime = substr((string) $data['appointment_time'], 0, 5);

        return $oldDate !== $newDate || $oldTime !== $newTime;
    }

    /** Returns the validated questionnaire, or null when it was not edited. */
    private function validatedQuestionnaire(AppointmentRequest $request, Appointment $appointment): ?array
    {
        if (! is_array($request->input('questionnaire'))) {
            return null;
        }

        $sections = MedicalQuestionnaire::sections();

        $questionKeys = collect($sections['general'])->pluck('key');
        if (MedicalQuestionnaire::showsWomenSection($appointment->patient)) {
            $questionKeys = $questionKeys->merge(collect($sections['women'])->pluck('key'));
        }

        $rules = [
            'questionnaire.answers' => ['required', 'array'],
            'questionnaire.conditions' => ['present', 'array'],
            'questionnaire.conditions.*' => ['string', Rule::in(collect($sections['conditions'])->pluck('key')->all())],
            'questionnaire.agreed' => ['accepted'],
        ];

        foreach ($questionKeys as $key) {
            $rules["questionnaire.answers.{$key}"] = ['required', 'boolean'];
        }

        return $request->validate($rules)['questionnaire'];
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

 /** Users with the dentist role, for the dropdowns. */
private function dentists()
{
    return User::role('dentist')
        ->where('is_active', IsActiveEnum::ACTIVE->value)
        ->select('id', 'name')
        ->with('dentistDetail:id,user_id,specialization,available_schedule')
        ->orderBy('name')
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