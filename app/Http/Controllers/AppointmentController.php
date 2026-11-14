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

class AppointmentController extends Controller
{
    /** Columns needed so Patient's full_name accessor can be built. */
    private const PATIENT_NAME_COLUMNS = ['id', 'first_name', 'middle_name', 'last_name'];

    public function __construct(private DentistAvailability $availability) {}

    public function index(SearchRequest $request): Response
    {
        $request->validated();

        $search = $request->string('search')->trim()->toString();

        $appointments = Appointment::query()
            ->with([
                'staff:id,name',
                'patient:' . implode(',', self::PATIENT_NAME_COLUMNS),
                'procedure:id,name',
                'dentist:id,name',
            ])
            ->where('is_approve', IsApproveEnum::Approve->value)
            ->when(
                $search,
                fn ($query, $s) => $query->whereHas('patient', fn ($p) => $p->searchName($s))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response|RedirectResponse
    {
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

    /** JSON: free "HH:mm" times for one dentist on one day. Pass appointment_id when editing. */
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

    /** Staff booking. Needs a questionnaire answered in this session. Approved at once. */
    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            return DB::transaction(function () use ($data) {
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

                $pending = session('pending_questionnaire');

                if (! $pending) {
                    return redirect()
                        ->route('appointments.questionnaire')
                        ->with('error', 'Answer or skip the medical questionnaire first.');
                }

                $patientId = (int) $data['patient_id'];
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

                $appointment = Appointment::create([
                    ...Arr::except($data, ['status']),
                    'staff_id' => auth()->id(),
                    'status' => AppointmentStatusEnum::Scheduled->value,
                    'is_approve' => IsApproveEnum::Approve->value,
                ]);

                MedicalQuestionnaire::create([
                    'patient_id' => $patientId,
                    'appointment_id' => $appointment->id,
                    'answers' => $answers,
                    'conditions' => $pending['conditions'],
                    'agreed' => $pending['agreed'],
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
        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'patients' => $this->activePatients(),
            'staff' => User::select('id', 'name')->orderBy('name')->get(),
            'procedures' => Procedure::select('id', 'name')->orderBy('name')->get(),
            'dentists' => $this->dentists(),
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($appointment->patient),
            'questionnaire' => $this->questionnaireData($appointment->questionnaire),
        ]);
    }

    /** Staff: read-only questionnaire of this appointment. */
    public function showQuestionnaire(Appointment $appointment): Response
    {
        $patient = $appointment->patient;

        return Inertia::render('Appointments/Questionnaire', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
            ],
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($patient),
            'questionnaire' => $appointment->questionnaire ? [
                'answers' => $appointment->questionnaire->answers,
                'conditions' => $appointment->questionnaire->conditions,
                'updated_at' => $appointment->questionnaire->updated_at?->format('M j, Y g:i A'),
            ] : null,
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        // Validated outside the try so errors show on the form instead of being swallowed.
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
                    $locked->is_approve = IsApproveEnum::Approve->value;
                }

                $locked->save();

                if ($questionnaire) {
                    MedicalQuestionnaire::updateOrCreate(
                        ['appointment_id' => $appointment->id],
                        [
                            'patient_id' => $appointment->patient_id,
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
     * Cancel. The row is kept for history.
     * A request that was never approved is also rejected, so it leaves the approval queue.
     * An approved booking keeps its approval.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($appointment) {
                $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                if ($this->hasTreatment($locked)) {
                    return ['error', 'This appointment has a treatment assigned and cannot be cancelled.'];
                }

                if ($this->enumValue($locked->status) === AppointmentStatusEnum::Cancelled->value) {
                    return ['message', 'Appointment is already cancelled.'];
                }

                $locked->status = AppointmentStatusEnum::Cancelled->value;

                if (in_array($this->enumValue($locked->is_approve), [
                    IsApproveEnum::Pending->value,
                    IsApproveEnum::PendingReScheduled->value,
                ], true)) {
                    $locked->is_approve = IsApproveEnum::Reject->value;
                }

                $locked->save();

                return ['message', 'Appointment cancelled successfully.'];
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('appointments.index')->with('error', 'Failed to cancel appointment.');
        }

        return redirect()->route('appointments.index')->with($result[0], $result[1]);
    }

    /** Compares date and HH:mm time only, so "09:00" and "09:00:00" are the same. */
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

    /** Questionnaire data for the edit page, or null when none exists. */
    private function questionnaireData(?MedicalQuestionnaire $questionnaire): ?array
    {
        return $questionnaire ? [
            'answers' => $questionnaire->answers,
            'conditions' => $questionnaire->conditions,
        ] : null;
    }

    private function activePatients()
    {
        return Patient::where('is_active', IsActiveEnum::ACTIVE->value)
            ->select(self::PATIENT_NAME_COLUMNS)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function dentists()
    {
        return User::role('dentist')
            ->where('is_active', IsActiveEnum::ACTIVE->value)
            ->select('id', 'name')
            ->with('dentistDetail:id,user_id,specialization,available_schedule')
            ->orderBy('name')
            ->get();
    }

    /** A treatment row, or a completed status, means the service was delivered. */
    private function hasTreatment(Appointment $appointment): bool
    {
        return $appointment->treatment()->exists()
            || $this->enumValue($appointment->status) === AppointmentStatusEnum::Completed->value;
    }

    /** Works whether or not the model casts the column to an enum. */
    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}