<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPrescriptionDetails extends Model
{
  protected $fillable = [
    'treatment_id',
    'medication_id',
    'dosage',
    'frequency',
    'duration_days',
];
public $timestamps = false;

    function treatment(){
        return $this->belongsTo(Treatment::class);
    }

    function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
    

}
