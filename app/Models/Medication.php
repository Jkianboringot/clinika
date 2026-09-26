<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasFactory;
    public $guarded = ['id'];

    // TreatmentPrescriptionDetails.php — ALSO WRONG
  function treatmentPrescriptions()
    {
        return $this->hasMany(TreatmentPrescriptionDetails::class, 'medication_id');
    }
}
