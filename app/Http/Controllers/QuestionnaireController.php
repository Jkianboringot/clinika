<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalQuestionnaireRequest;
use App\Models\MedicalQuestionnaire;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff side: read-only patient view, plus the questionnaire that is
 * filled in before booking (attached to the patient when saved).
 */
class QuestionnaireController extends Controller
{
    public function show(Patient $patient): Response
    {
        $questionnaire = MedicalQuestionnaire::where('patient_id', $patient->id)->first();

        return Inertia::render('Patients/Questionnaire', [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
            ],
            'sections' => MedicalQuestionnaire::sections(),
            'questionnaire' => $questionnaire ? [
                'answers' => $questionnaire->answers,
                'conditions' => $questionnaire->conditions,
                'updated_at' => $questionnaire->updated_at?->format('M j, Y g:i A'),
            ] : null,
        ]);
    }

    /** Questionnaire shown before a patient is chosen. */
    public function pendingFill(): Response
    {
        return Inertia::render('Patient/Questionnaire', [
            'sections' => MedicalQuestionnaire::sections(),
            // Patient not chosen yet, so the women's section is hidden.
            'isFemale' => false,
            'saved' => session('pending_questionnaire') ? [
                'answers' => session('pending_questionnaire')['answers'],
                'conditions' => session('pending_questionnaire')['conditions'],
            ] : null,
            'postUrl' => route('appointments.questionnaire.save'),
            'skipUrl' => route('appointments.questionnaire.skip'),
            'canSkip' => true,
        ]);
    }

    public function pendingSave(MedicalQuestionnaireRequest $request): RedirectResponse
    {
        $data = $request->validated();

        session([
            'pending_questionnaire' => [
                'answers' => collect($data['answers'])
                    ->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
                    ->all(),
                'conditions' => array_values(array_unique($data['conditions'])),
                'agreed' => true,
            ],
        ]);

        return redirect()
            ->route('appointments.create')
            ->with('message', 'Questionnaire saved. Now choose the patient.');
    }

    /** Staff only: every question set to No. */
    public function pendingSkip(): RedirectResponse
    {
        session([
            'pending_questionnaire' => [
                'answers' => array_fill_keys(array_keys(config('medical_questionnaire.general')), false),
                'conditions' => [],
                'agreed' => false,
            ],
        ]);

        return redirect()
            ->route('appointments.create')
            ->with('message', 'Questionnaire skipped. All answers set to No.');
    }
}