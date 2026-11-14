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

        return Inertia::render('Patients/Appointment/Questionnaire', [
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
//NOTE:tem fix

          session()->forget('pending_questionnaire');
        return Inertia::render('Patient/Questionnaire', [
            'sections' => MedicalQuestionnaire::sections(),
            // Patient not chosen yet, so show the women's section as optional.
            // It is dropped later if the chosen patient is not female.
            'isFemale' => true,
            'womenOptional' => true,
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
                    ->reject(fn ($value) => $value === null)
                    ->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
                    ->all(),
                'conditions' => array_values(array_unique($data['conditions'] ?? [])),
                'agreed' => true,
            ],
        ]);

        return redirect()
            ->route('appointments.create')
            ->with('message', 'Questionnaire saved. Now choose the patient.');
    }

    /** Staff only: every question (general + women) set to No. */
    public function pendingSkip(): RedirectResponse
    {
        $sections = MedicalQuestionnaire::sections();

        $keys = collect($sections['general'])
            ->merge($sections['women'])
            ->pluck('key')
            ->all();

        session([
            'pending_questionnaire' => [
                'answers' => array_fill_keys($keys, false),
                'conditions' => [],
                'agreed' => false,
            ],
        ]);

        return redirect()
            ->route('appointments.create')
            ->with('message', 'Questionnaire skipped. All answers set to No.');
    }
}