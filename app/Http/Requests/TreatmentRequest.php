<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'follow_up_schedule' => ['nullable', 'date'],
            'duration_minutes'   => ['required', 'integer', 'min:1', 'max:1440'],
            'summary'            => ['nullable', 'string', 'max:500'],

            // Items used (matches the form: itemList[].item_id / quantity)
            'itemList'            => ['required', 'array', 'min:1'],
            'itemList.*.item_id'  => ['required', 'exists:inventory_items,id'],
            'itemList.*.quantity' => ['required', 'numeric', 'min:1'],

            // Prescription (optional; matches the form: prescriptionlist[])
            'prescriptionlist'                   => ['nullable', 'array'],
            'prescriptionlist.*.medication_id'   => ['required', 'exists:medications,id'],
            'prescriptionlist.*.dosage'          => ['required', 'string', 'max:100'],
            'prescriptionlist.*.frequency'       => ['required', 'string', 'max:100'],
            'prescriptionlist.*.duration_days'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'itemList.required' => 'A treatment needs at least one item.',
            'itemList.min'      => 'A treatment needs at least one item.',
            'itemList.*.item_id.required' => 'Please select an item.',
            'itemList.*.quantity.min'     => 'Quantity must be at least 1.',

            'prescriptionlist.*.medication_id.required' => 'Please select a medication.',
            'prescriptionlist.*.dosage.required'        => 'Dosage is required.',
            'prescriptionlist.*.frequency.required'     => 'Frequency is required.',
            'prescriptionlist.*.duration_days.required' => 'Duration (days) is required.',
        ];
    }
}