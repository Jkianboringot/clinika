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
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'min:3',
                'confirmed',
            ],
        ];
    }
}