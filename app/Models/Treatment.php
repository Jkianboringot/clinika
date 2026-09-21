<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    public $guarded = ['id'];


    public function items(){
        return $this->hasMany(TreatmentInventory::class);
    }
}
