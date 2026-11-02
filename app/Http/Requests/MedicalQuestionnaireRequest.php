<?php

namespace App\Http\Requests;

use App\Models\MedicalQuestionnaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicalQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (array_keys(config('medical_questionnaire.general')) as $key) {
            $rules["answers.$key"] = ['required', 'boolean'];
        }

        $patient = $this->user('patient');

        if ($patient) {
            // Patient self-service: women questions are required, but only for female patients.
            if (MedicalQuestionnaire::showsWomenSection($patient)) {
                foreach (array_keys(config('medical_questionnaire.women')) as $key) {
                    $rules["answers.$key"] = ['required', 'boolean'];
                }
            }
        } else {
            // Staff flow: the patient is not chosen yet, so women questions are optional here.
            // They are kept or dropped later in AppointmentController@store, once the patient is known.
            foreach (array_keys(config('medical_questionnaire.women')) as $key) {
                $rules["answers.$key"] = ['nullable', 'boolean'];
            }
        }

        $rules['conditions'] = ['present', 'array'];
        $rules['conditions.*'] = ['string', Rule::in(array_keys(config('medical_questionnaire.conditions')))];
        $rules['agreed'] = ['accepted'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'answers.*.required' => 'Please answer every question.',
            'answers.*.boolean' => 'Please answer every question with Yes or No.',
            'agreed.accepted' => 'You must confirm that your answers are true and correct.',
        ];
    }
}