<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{

    public $guarded = ['id'];
    function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    function treatment(){
        return $this->hasOne(Treatment::class);
    }


    function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }


}
