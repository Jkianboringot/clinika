<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
       

        return [
          
            // 'appointment_id' => ['required', 'exists:appointments,id'],
            'follow_up_schedule' => ['nullable', 'date', 'today'],

            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'summary' => ['nullable', 'string', 'max:300'],

            // If you are also saving inventories together with treatment
            'treatmentItemList' => ['required', 'array'],
            'treatmentItemList.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'itemlist.*.quantity' => ['required', 'numeric', 'min:0.01'],
            // If you create multiple prescriptions at once for one treatment
            'prescriptions' => ['required', 'array', 'min:1'],
            'prescriptions.*.medication_id' => ['required', 'exists:medications,id'],
            'prescriptions.*.dosage' => ['required', 'string', 'max:100'],
            'prescriptions.*.frequency' => ['required', 'string', 'max:100'],
            'prescriptions.*.duration_days' => ['required', 'integer', 'min:1'],



        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.unique' => 'This appointment already has a treatment record.',
        ];
    }
}