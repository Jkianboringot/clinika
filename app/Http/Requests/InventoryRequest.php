<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [


            // Items used (matches the form: itemList[].item_id / quantity)
            'itemList' => ['required', 'array', 'min:1'],
            'itemList.*.item_id' => ['required', 'exists:inventory_items,id'],
            'itemList.*.quantity' => ['required', 'numeric', 'min:1','max:99999'],

        ];
    }


}