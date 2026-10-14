<?php

namespace App\Http\Requests\Settings;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user();

        if ($user instanceof Patient) {
            return [
                'first_name'  => ['required', 'string', 'min:2', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name'   => ['required', 'string', 'min:2', 'max:100'],
                'email'       => [
                    'required', 'string', 'email', 'max:150',
                    Rule::unique('patients', 'email')->ignore($user->id),
                ],
            ];
        }

        // Staff: keep your existing rules here if they differ.
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ];
    }
}