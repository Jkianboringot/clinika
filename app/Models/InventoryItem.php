<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;
    public $guarded = ['id'];


    public function treatment()
    {
        return $this->hasMany(Treatment::class);
    }
}
