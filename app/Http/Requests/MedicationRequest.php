<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        
        $med = $this->route('medications')?->id;
        return [
            'name' => ['required', 'string', 'min:2', 'max:150', Rule::unique('medications', 'name')->ignore($med),],
            'generic_name' => ['nullable', 'string', 'max:150'],
            'dosage_form' => ['nullable', 'string', 'max:75'],
            'strength' => ['nullable', 'string', 'max:50'],
        ];
    }
}