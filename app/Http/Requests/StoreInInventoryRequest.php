<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreInInventoryRequest extends FormRequest
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
            'branch_id' => ['required', 'exists:branches,id'],
            'stock_movement_type' => [
                'required',
                new Enum(StockMovementType::class),
            ],
            'productList' => ['required', 'array', 'max:99999', 'min:1'],
            'productList.*.product_id' => ['required', 'exists:products,id'],
            'productList.*.quantity' => [
                'required',
                'integer',
                'max:99999',
                'min:1',
            ],
        ];
    }
}
