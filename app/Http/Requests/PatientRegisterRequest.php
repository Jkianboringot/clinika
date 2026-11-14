<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PatientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Lowercase the email so it matches the password-reset flow, which also lowercases.
    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],

            'email' => [
                'required',
                'string',
                'email',
                'min:3',
                'max:150',
                Rule::unique('patients', 'email'),
            ],
            'contact_number' => ['required', 'regex:/^09\d{9}$/'],
            'address' => ['nullable', 'string', 'min:3', 'max:300'],

            'emergency_contact_name' => ['nullable', 'string', 'min:2', 'max:100', 'required_with:emergency_contact_number'],
            'emergency_contact_number' => ['nullable', 'regex:/^09\d{9}$/', 'required_with:emergency_contact_name'],

            'medical_history' => ['nullable', 'string', 'max:300'],
            'allergies' => ['nullable', 'string', 'max:300'],

            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Try signing in or resetting your password.',
            'contact_number.regex' => 'Enter an 11-digit mobile number starting with 09.',
            'emergency_contact_number.regex' => 'Enter an 11-digit mobile number starting with 09.',
        ];
    }
}