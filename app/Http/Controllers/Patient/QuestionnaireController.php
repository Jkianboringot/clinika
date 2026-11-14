<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalQuestionnaireRequest;
use App\Models\MedicalQuestionnaire;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    public function edit(): Response
    {
        $patient = auth('patient')->user();

        $questionnaire = MedicalQuestionnaire::where('patient_id', $patient->id)->first();

        return Inertia::render('Patient/Questionnaire', [
            'sections' => MedicalQuestionnaire::sections(),
            'isFemale' => MedicalQuestionnaire::showsWomenSection($patient),
            'saved' => $questionnaire ? [
                'answers' => $questionnaire->answers,
                'conditions' => $questionnaire->conditions,
            ] : null,
        ]);
    }

    public function store(MedicalQuestionnaireRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // The patient always comes from the login, never from the request.
        $patientId = auth('patient')->id();

        try {
            MedicalQuestionnaire::updateOrCreate(
                ['patient_id' => $patientId],
                [
                  'answers' => collect($data['answers'])
    ->reject(fn ($value) => $value === null)   // add this line
    ->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
    ->all(),
                    'conditions' => array_values(array_unique($data['conditions'])),
                    'agreed' => true,
                ],
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to save your questionnaire. Please try again.');
        }

        return redirect()
            ->route('patient.appointments.create')
            ->with('message', 'Medical questionnaire saved. You can now book an appointment.');
    }

    public function pendingFill(): Response
{
    return Inertia::render('Patients/Appointment/Questionnaire', [
        'sections' => MedicalQuestionnaire::sections(),
        'isFemale' => true,
        'appointment' => null,
        'initial' => session('pending_questionnaire') ? [
            'answers' => session('pending_questionnaire')['answers'],
            'conditions' => session('pending_questionnaire')['conditions'],
        ] : null,
    ]);
}
}