<?php

namespace App\Models;

use App\Enums\IsActiveEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Patient  extends Authenticatable
{

    use Notifiable;
    use HasFactory;

    public $guarded = ['id'];
    // app/Models/Patient.php
    protected $hidden = ['password'];

    







    protected function casts(): array
{
    return [
        // ...your existing casts
        'is_active' => IsActiveEnum::class,
    ];
}


    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
