<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name'
        ,'contact'
        ,'email'
    ];

    public function getFullNameAttribute()
    {
        $middle_name = $this->middle_name ?? '';
        return trim("{$this->first_name} {$middle_name} {$this->last_name}");
    }
}
