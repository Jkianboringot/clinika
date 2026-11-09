<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatusEnum;
use App\Enums\IsApproveEnum;
use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(private DentistAvailability $availability) {}

    public function index()
    {
        $appointments = Appointment::where('patient_id', auth('patient')->id())
            ->with(['procedure:id,name', 'dentist:id,name'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(10);

        return Inertia::render('Patients/Appointment/Index', [
            'appointments' => $appointments,
        ]);
    }

    /** New booking. Questionnaire first, every time, same as staff. */
    public function create(): Response|RedirectResponse
    {
        if (! session()->has('pending_questionnaire')) {
            return redirect()->route('patient.appointments.questionnaire');
        }

        return $this->bookingPage();
    }

    /** Empty questionnaire page for a new booking. */
    public function questionnaire(): Response
    {
        return Inertia::render('Patients/Appointment/Questionnaire', [
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($this->patient()),
            'appointment' => null,
            'initial' => null,
        ]);
    }

    /** Holds the answers in the session until the booking is submitted. */
    public function saveQuestionnaire(Request $request): RedirectResponse
    {
        $data = $request->validate($this->questionnaireRules());

        session([
            'pending_questionnaire' => [
                'answers' => $this->normalizeAnswers($data['answers']),
                'conditions' => array_values(array_unique($data['conditions'] ?? [])),
                'agreed' => true,
            ],
        ]);

        return redirect()->route('patient.appointments.create');
    }

    /** Questionnaire for one appointment, prefilled with the patient's current answers. */
    public function editQuestionnaire(Appointment $appointment): Response|RedirectResponse
    {
        if ($message = $this->blockReason($appointment)) {
            return redirect()->route('patient.appointments.index')->with('error', $message);
        }

        $existing = MedicalQuestionnaire::where('patient_id', $appointment->patient_id)->first();

        return Inertia::render('Patients/Appointment/Questionnaire', [
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($this->patient()),
            'appointment' => ['id' => $appointment->id],
            'initial' => $existing ? [
                'answers' => $existing->answers,
                'conditions' => $existing->conditions,
            ] : null,
        ]);
    }

    /** Saves the questionnaire to the patient's record. */
    public function updateQuestionnaire(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($message = $this->blockReason($appointment)) {
            return redirect()->route('patient.appointments.index')->with('error', $message);
        }

        $data = $request->validate($this->questionnaireRules());

        MedicalQuestionnaire::updateOrCreate(
            ['patient_id' => $appointment->patient_id],
            [
                'answers' => $this->normalizeAnswers($data['answers']),
                'conditions' => array_values(array_unique($data['conditions'] ?? [])),
                'agreed' => true,
            ],
        );

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', 'Medical questionnaire updated.');
    }

    /** JSON: day colours for one month. Pass appointment_id when rescheduling. */
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

    /** JSON: free times for one dentist on one day. */
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

    /** Submit a new booking. Saves the questionnaire, then the appointment as pending. */
    public function store(Request $request): RedirectResponse
    {
        $pending = session('pending_questionnaire');

        if (! $pending) {
            return redirect()->route('patient.appointments.questionnaire');
        }

        $data = $request->validate($this->rules());
        $patientId = (int) auth('patient')->id();

        try {
            return DB::transaction(function () use ($data, $pending, $patientId) {
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

                MedicalQuestionnaire::updateOrCreate(
                    ['patient_id' => $patientId],
                    [
                        'answers' => $pending['answers'],
                        'conditions' => $pending['conditions'],
                        'agreed' => $pending['agreed'],
                    ],
                );

                Appointment::create([
                    'patient_id' => $patientId,
                    'procedure_id' => $data['procedure_id'],
                    'dentist_id' => $dentist->id,
                    'appointment_date' => $data['appointment_date'],
                    'appointment_time' => $data['appointment_time'],
                    'description' => $data['description'] ?? null,
                    'status' => AppointmentStatusEnum::Scheduled->value,
                    'is_approve' => IsApproveEnum::Pending->value,
                ]);

                session()->forget('pending_questionnaire');

                return redirect()
                    ->route('patient.appointments.index')
                    ->with('success', 'Appointment request sent. The clinic will confirm it.');
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'appointment_time' => 'Failed to book the appointment. Please try again.',
            ]);
        }
    }

    /** Reschedule: same calendar page, filled with the current booking. */
    public function edit(Appointment $appointment): Response|RedirectResponse
    {
        if ($message = $this->blockReason($appointment)) {
            return redirect()->route('patient.appointments.index')->with('error', $message);
        }

        return $this->bookingPage([
            'id' => $appointment->id,
            'procedure_id' => (string) $appointment->procedure_id,
            'dentist_id' => (string) $appointment->dentist_id,
            'description' => $appointment->description ?? '',
            'appointment_date' => Carbon::parse($appointment->appointment_date)->toDateString(),
            'appointment_time' => substr((string) $appointment->appointment_time, 0, 5),
        ]);
    }

    /** Reschedule submit. A moved booking goes back to pending reschedule. */
    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($message = $this->blockReason($appointment)) {
            return redirect()->route('patient.appointments.index')->with('error', $message);
        }

        $data = $request->validate($this->rules());

        try {
            return DB::transaction(function () use ($data, $appointment) {
                $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                $dentist = User::whereKey($data['dentist_id'])->lockForUpdate()->firstOrFail();

                $moved = $this->scheduleChanged($locked, $data)
                    || (int) $locked->dentist_id !== (int) $data['dentist_id'];

                if ($moved) {
                    $conflict = $this->availability->conflict(
                        $dentist,
                        $data['appointment_date'],
                        $data['appointment_time'],
                        $locked->id,
                    );

                    if ($conflict) {
                        return back()->withInput()->withErrors([
                            $conflict['field'] => $conflict['message'],
                        ]);
                    }
                }

                $locked->fill([
                    'procedure_id' => $data['procedure_id'],
                    'dentist_id' => $dentist->id,
                    'appointment_date' => $data['appointment_date'],
                    'appointment_time' => $data['appointment_time'],
                    'description' => $data['description'] ?? null,
                ]);

                if ($moved) {
                    $locked->status = AppointmentStatusEnum::Rescheduled->value;
                    $locked->is_approve = IsApproveEnum::PendingReScheduled->value;
                }

                $locked->save();

                return redirect()
                    ->route('patient.appointments.index')
                    ->with('success', $moved
                        ? 'Reschedule request sent. The clinic will confirm it.'
                        : 'Appointment updated.');
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'appointment_time' => 'Failed to update the appointment. Please try again.',
            ]);
        }
    }

    /** Cancel. The row is kept and its slot is freed. */
    public function cancel(Appointment $appointment): RedirectResponse
    {
        if ($message = $this->blockReason($appointment)) {
            return redirect()->route('patient.appointments.index')->with('error', $message);
        }

        $appointment->update(['status' => AppointmentStatusEnum::Cancelled->value]);

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', 'Appointment cancelled.');
    }

   private function bookingPage(?array $appointment = null): Response
{
    return Inertia::render('Patients/Appointment/Create', [
        'dentists' => $this->availability->dentists()
            ->load('dentistDetail:id,user_id,specialization,available_schedule')
            ->map(fn (User $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'dentist_detail' => $d->dentistDetail ? [
                    'specialization' => $d->dentistDetail->specialization,
                    'available_schedule' => $d->dentistDetail->available_schedule,
                ] : null,
            ])
            ->values(),
        'procedures' => Procedure::orderBy('name')->get(['id', 'name']),
        'appointment' => $appointment,
    ]);
}
    private function patient(): Patient
    {
        return Patient::findOrFail(auth('patient')->id());
    }

    /** Only the owner can change it, and not after a treatment or cancellation. */
    private function blockReason(Appointment $appointment): ?string
    {
        abort_unless((int) $appointment->patient_id === (int) auth('patient')->id(), 404);

        if ($appointment->treatment()->exists()) {
            return "This appointment already has a treatment and can't be changed.";
        }

        $status = $this->value($appointment->status);

        if ($status === AppointmentStatusEnum::Cancelled->value) {
            return 'This appointment is already cancelled.';
        }

        if ($status === AppointmentStatusEnum::Completed->value) {
            return 'This appointment is already completed.';
        }

        return null;
    }

    private function scheduleChanged(Appointment $appointment, array $data): bool
    {
        $oldDate = Carbon::parse($appointment->appointment_date)->toDateString();
        $oldTime = substr((string) $appointment->appointment_time, 0, 5);

        return $oldDate !== $data['appointment_date'] || $oldTime !== $data['appointment_time'];
    }

    private function rules(): array
    {
        return [
            'procedure_id' => ['required', 'exists:procedures,id'],
            'dentist_id' => ['required', 'exists:users,id'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Questionnaire rules, built from the same sections as staff. */
    private function questionnaireRules(): array
    {
        $sections = MedicalQuestionnaire::sections();
        $isFemale = MedicalQuestionnaire::showsWomenSection($this->patient());

        $keys = collect($sections['general'])->pluck('key');
        if ($isFemale) {
            $keys = $keys->merge(collect($sections['women'])->pluck('key'));
        }

        $rules = [
            'answers' => ['required', 'array'],
            'conditions' => ['present', 'array'],
            'conditions.*' => ['string', Rule::in(collect($sections['conditions'])->pluck('key')->all())],
            'agreed' => ['accepted'],
        ];

        foreach ($keys as $key) {
            $rules["answers.{$key}"] = ['required', 'boolean'];
        }

        return $rules;
    }

    private function normalizeAnswers(array $answers): array
    {
        return collect($answers)
            ->map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN))
            ->all();
    }

    /** Works whether or not the model casts the column to an enum. */
    private function value(mixed $v): ?string
    {
        return $v instanceof \BackedEnum ? $v->value : $v;
    }
}