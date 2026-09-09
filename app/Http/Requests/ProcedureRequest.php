<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization already handled in the controller via $this->authorize()
    }

    public function rules(): array
    {
        $procedureId = $this->route('procedure')?->id;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('procedures', 'name')->ignore($procedureId),
            ],
        ];
    }
}