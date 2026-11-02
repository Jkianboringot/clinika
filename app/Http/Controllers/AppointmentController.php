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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
 *
 * QUESTIONNAIRE ON EDIT
 * ---------------------
 * Staff can optionally edit the patient's medical questionnaire from the
 * edit page. It is only saved when the page sends a "questionnaire"
 * block, and it is saved inside the same locked transaction as the
 * appointment (so the treatment guard covers it too).
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

public function store(AppointmentRequest $request): RedirectResponse
{
    try {
        $patientId = (int) $request->validated()['patient_id'];

        $pending = session('pending_questionnaire');

        if ($pending) {
            $patient = Patient::findOrFail($patientId);
            $answers = $pending['answers'];

            $womenKeys = collect(MedicalQuestionnaire::sections()['women'])->pluck('key')->all();

            if (MedicalQuestionnaire::showsWomenSection($patient)) {
                // Female patient: every women question must be answered.
                if (count(array_diff($womenKeys, array_keys($answers))) > 0) {
                    return redirect()
                        ->route('appointments.questionnaire')
                        ->with('error', 'This patient is female. Please answer the Women questions too.');
                }
            } else {
                // Not female: the women answers do not belong on this record.
                $answers = Arr::except($answers, $womenKeys);
            }

            // Attach the questionnaire just filled in to the chosen patient.
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
            // status is never taken from the request
            ...Arr::except($request->validated(), ['status']),
            'staff_id' => auth()->id(),
            'status' => AppointmentStatusEnum::Scheduled->value,
            // staff (logged-in user) -> approved right away, anyone else -> pending
            'is_approve' => auth()->id() ? IsApproveEnum::Approve->value : IsApproveEnum::Pending->value,
        ]);

        session()->forget('pending_questionnaire');
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

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        // Validated OUTSIDE the try so errors show on the form instead of being swallowed.
        $questionnaire = $this->validatedQuestionnaire($request, $appointment);

        try {
            $refusal = DB::transaction(function () use ($request, $appointment, $questionnaire) {
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
                    // staff (logged-in user) -> stays approved, anyone else -> pending reschedule
                    $locked->is_approve = auth()->id()
                        ? IsApproveEnum::Approve->value
                        : IsApproveEnum::PendingReScheduled->value;
                }

                $locked->save();

                // Only when the staff opened the questionnaire section.
                // Saved to the patient whose answers were shown on the page.
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
            ->where('is_active', IsActiveEnum::ACTIVE->value) // remove if users has no is_active column
            ->select('id', 'name')
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