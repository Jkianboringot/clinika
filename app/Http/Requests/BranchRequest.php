<?php

namespace App\Http\Requests;

use App\Enums\BranchType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
                'name' => ['required', 'string', 'max:75', 'min:3', 'unique:branches,name'],
                'location' => ['nullable', 'string', 'max:100', 'min:3'],
                'branch_type' => ['required', new Enum(BranchType::class)],
        ];
    }
}
