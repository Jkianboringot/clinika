<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inventory extends Model
{

    use HasFactory;
    public $guarded = ['id'];

    public function sales()
    {
        return $this->hasOne(Sale::class);
    }


    public function branchs()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoder_id');
    }

}
