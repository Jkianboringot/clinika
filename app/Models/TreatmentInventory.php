<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentInventory extends Model
{
    public function items()
    {
        return $this->belongsTo(InventoryItem::class);
    }

      public function treatments()
    {
        return $this->belongsTo(Treatment::class);
    }
}
