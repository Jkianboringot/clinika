<?php

namespace App\Http\Requests;

use App\Enums\Shift;
use App\Enums\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOutInventoryRequest extends FormRequest
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
        return[
            'branch_id' => ['required', 'exists:branches,id'],
            'productList' => ['required', 'array', 'min:1'],
            'productList.*.product_id' => ['required', 'exists:products,id'],
            'productList.*.quantity' => ['required', 'integer', 'max:99999','min:1'],
            'shift' => ['required', new Enum(Shift::class)],
            'stock_movement_type' => ['required', new Enum(StockMovementType::class)],
            'cash_amount' => ['required', 'numeric', 'max:99999','min:0.01'],
            'gcash_amount' => ['nullable', 'numeric', 'max:99999'],
            'cash_advance' => ['nullable', 'numeric', 'max:99999'],
            'remitted_expenses' => ['nullable', 'numeric', 'max:99999'],
            'cash_shortage' => ['nullable', 'numeric', 'max:99999'],
            'net_cash' => ['required', 'numeric', 'max:99999','min:0.01'],
        ];
    }
}
