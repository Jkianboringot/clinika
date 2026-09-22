<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    public $guarded = ['id'];


    public function treatmentInventories(){
        return $this->hasMany(TreatmentInventory::class);
    }

    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
    
    public function perscriptions(){
        return $this->hasMany(TreatmentPrescriptionDetails::class);
    }
}
