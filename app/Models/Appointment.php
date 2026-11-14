<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class Appointment extends Model
{

    use HasFactory;
    public $guarded = ['id'];

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
    function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    function treatment()
    {
        return $this->hasOne(Treatment::class, 'appointment_id');
    }

    public function questionnaire(): HasOneOrMany
    {
        return $this->hasOne(MedicalQuestionnaire::class, 'appointment_id');
    }


    function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }


}
