<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization already handled in the controller via $this->authorize()
    }

    public function rules(): array
    {

        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'emergency_contact_number' => [
                'nullable',
                'string',
                'min:11',
            ],
            'emergency_contact_name' => [
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
            'contact_number' => [
                'nullable',
                'string',
                'min:11',
            ],
            'medical_history' => ['nullable', 'string', 'min:3', 'max:300'],
            'address' => ['nullable', 'string', 'min:3', 'max:300'],
            'allergies' => ['nullable', 'string', 'min:3', 'max:300'],

            'gender' => ['required', Rule::enum(GenderEnum::class)],

            'date_of_birth' => [
                'required',
                'date',
                'before:today',
            ],

            'email' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('patients', 'email')->ignore($this->route('patients')),
            ],
            'password' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}