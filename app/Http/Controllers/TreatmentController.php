<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatusEnum as EnumsAppointmentStatusEnum;
use App\Exceptions\TreatmentLimitExceededException;
use App\Http\Requests\TreatmentRequest;
use App\Mail\TreatmentSummaryMail;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Medication;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Services\TreatmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

// A treatment can't be created without an appointment; the patient comes from the
// appointment, and the treatment is what triggers the stock reduction.

class TreatmentController extends Controller
{
    public function __construct(protected TreatmentService $treatmentService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $treatments = Treatment::with('appointment.patient')
            ->when($search, fn($q) => $q->whereHas(
                'appointment.patient',
                fn($p) => $p->where('full_name', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($t) => [
                'id' => $t->id,
                'patient_name' => $t->appointment?->patient?->full_name,
                'appointment_id' => $t->appointment_id,
                'follow_up_schedule' => $t->follow_up_schedule,
                'duration_minutes' => $t->duration_minutes,
                'summary' => $t->summary,
                'created_at' => $t->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Treatments/Index', [
            'treatments' => $treatments,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Appointment $appointment)
    {
        return Inertia::render('Treatments/Create', [
            'appointment' => $appointment->load('patient'),
            'items' => InventoryItem::selectRaw('id, item_name as name')->orderBy('item_name')->get(),
            'procedures' => Procedure::all(),
            'medications' => Medication::all()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name ?? $m->medication_name ?? $m->generic_name ?? "Medication #{$m->id}",
            ]),
            'followUpEnum' => collect(EnumsAppointmentStatusEnum::cases())->map(fn($a) => [
                'value' => $a->value,
                'label' => $a->value,
            ]),
        ]);
    }

    // Route::post('{appointment}/create')
    public function store(Appointment $appointment, TreatmentRequest $request)
    {
        $data = $request->validated();

        try {
            $tr = $this->treatmentService->treatmentStore(
                $appointment,
                [
                    'treatment' => [
                        'follow_up_schedule' => $data['follow_up_schedule'],
                        'duration_minutes' => $data['duration_minutes'],
                        'summary' => $data['summary'],
                    ],
                    'prescriptionlist' => $data['prescriptionlist'],
                    'itemList' => $data['itemList'],
                ]
            );
        } catch (\Throwable $e) {
            return $this->failToIndex($e);
        }

        if (!$tr) {
            return back()->with('error', 'Failed to create treatment');
        }

        // After commit. A mail failure must never undo the treatment.
        $this->sendSummaryEmail($appointment, $tr);

        return redirect()->route('treatments.index')
            ->with('message', $this->withWarnings('Successfully created treatment', $tr->low_stock_warnings ?? []));
    }

    public function edit(Treatment $treatment)
    {
        $treatment->load(['treatmentInventories', 'perscriptions']);

        $appointment = Appointment::with('patient')->findOrFail($treatment->appointment_id);

        return Inertia::render('Treatments/Edit', [
            'treatment' => [
                'id' => $treatment->id,
                'follow_up_schedule' => $treatment->follow_up_schedule,
                'duration_minutes' => $treatment->duration_minutes,
                'summary' => $treatment->summary,
                'itemList' => $treatment->treatmentInventories->map(fn($ti) => [
                    'item_id' => $ti->inventory_item_id,
                    'quantity' => $ti->quantity,
                ]),
                'prescriptionlist' => $treatment->perscriptions->map(fn($p) => [
                    'medication_id' => $p->medication_id,
                    'dosage' => $p->dosage,
                    'frequency' => $p->frequency,
                    'duration_days' => $p->duration_days,
                ]),
            ],
            'appointment' => $appointment,
            'items' => InventoryItem::all()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->item_name,
            ]),
            'procedures' => Procedure::all()->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]),
            'medications' => Medication::all()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name ?? $m->medication_name ?? $m->generic_name ?? "Medication #{$m->id}",
            ]),
        ]);
    }

    public function update(Treatment $treatment, TreatmentRequest $request)
    {
        $data = $request->validated();

        try {
            $tr = $this->treatmentService->treatmentUpdate(
                $treatment,
                [
                    'treatment' => [
                        'follow_up_schedule' => $data['follow_up_schedule'],
                        'duration_minutes' => $data['duration_minutes'],
                        'summary' => $data['summary'],
                    ],
                    'prescriptionlist' => $data['prescriptionlist'],
                    'itemList' => $data['itemList'],
                ]
            );
        } catch (\Throwable $e) {
            return $this->failToIndex($e);
        }

        if (!$tr) {
            return back()->with('error', 'Failed to update treatment');
        }

        return redirect()->route('treatments.index')
            ->with('message', $this->withWarnings('Successfully updated treatment', $tr->low_stock_warnings ?? []));
    }

    public function deletePreview(Treatment $treatment)
    {
        return response()->json($this->treatmentService->treatmentDeletePreview($treatment));
    }

    public function destroy(Treatment $treatment)
    {
        try {
            $impact = $this->treatmentService->treatmentDelete($treatment);
        } catch (\Throwable $e) {
            return redirect()->route('treatments.index')->with('error', 'Failed to delete treatment.');
        }

        return redirect()->route('treatments.index')
            ->with('message', $this->withWarnings('Treatment deleted and stock returned.', $impact['warnings'] ?? []));
    }

    /**
     * Turns a failed store/update into a redirect to the index page with an
     * 'error' flash, which Index.tsx shows as a toast. The service already
     * logs unexpected failures, so no logging here.
     */
    private function failToIndex(\Throwable $e)
    {
        // Expected, user-facing failures: 1-treatment-per-appointment rule,
        // insufficient stock, missing inventory item.
        if ($e instanceof TreatmentLimitExceededException || $e instanceof \RuntimeException) {
            $message = $e->getMessage();
        } else {
            $message = 'Something went wrong while saving the treatment. Please try again.';
        }

        return redirect()->route('treatments.index')->with('error', $message);
    }

    /**
     * Only 'message' and 'error' flash keys exist in the UI, so low-stock
     * warnings are folded into the message text.
     */
    private function withWarnings(string $message, array $warnings): string
    {
        return $warnings ? $message . ' Note: ' . implode(' ', $warnings) : $message;
    }

    /**
     * Emails the patient their treatment summary (Mailtrap in dev via .env).
     * Never throws.
     */
  private function sendSummaryEmail(Appointment $appointment, Treatment $tr): void
{
    $patient = $appointment->patient;

    if (!$patient?->email) {
        Log::info('Treatment summary email skipped: patient has no email', [
            'treatment_id' => $tr->id,
            'patient_id' => $patient?->id,
        ]);
        return;
    }

    try {
        Mail::to($patient->email)->send(new TreatmentSummaryMail($tr));

        Log::info('Treatment summary email sent', [
            'treatment_id' => $tr->id,
            'patient_id' => $patient->id,
            'to' => $patient->email,
        ]);
    } catch (\Throwable $e) {
        Log::error('Treatment summary email failed', [
            'treatment_id' => $tr->id,
            'patient_id' => $patient->id,
            'to' => $patient->email,
            'error' => $e->getMessage(),
        ]);
    }
}
}