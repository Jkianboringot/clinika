<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    public $guarded = ['id'];
    // app/Models/Patient.php
    protected $hidden = ['password'];
    use HasFactory;

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
