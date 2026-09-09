<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'min:2', 'max:75'],
            'minumun_stock' => ['nullable', 'integer', 'max:99999','min:1'],
        ];
    }
}