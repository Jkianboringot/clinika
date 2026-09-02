<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    public $fillable = ['name', 'pieces_per_unit'];


    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }//one to many
}
