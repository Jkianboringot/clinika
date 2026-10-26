<?php

namespace App\Http\Requests;

use App\Enums\IsActiveEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Real authorization (permissions + role-assignment safeguards) lives
        // in the controller — it depends on business rules, not just "is this
        // user logged in and submitting a form".
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->password === '') {
            $this->merge(['password' => null]);
        }

        if ($this->password_confirmation === '') {
            $this->merge(['password_confirmation' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:75'],
            'email' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'dentist', 'staff'])],
            'is_active' => ['sometimes', Rule::enum(IsActiveEnum::class)],
            'password' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'dentist' => ['nullable', 'array'],
            'dentist.specialization' => ['required_if:role,dentist', 'nullable', 'string', 'max:255'],
            'dentist.appointment_limit' => ['required_if:role,dentist', 'nullable', 'integer', 'min:1', 'max:50'],
            'dentist.schedule' => ['required_if:role,dentist', 'nullable', 'array', 'min:1'],
            'dentist.schedule.*.day' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'dentist.schedule.*.start' => ['required', 'date_format:H:i'],
            'dentist.schedule.*.end' => ['required', 'date_format:H:i', 'after:dentist.schedule.*.start'],
        ];
    }
}