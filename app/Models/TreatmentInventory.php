<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentInventory extends Model
{
    public $guarded = ['id'];
    public function items()
    {
        return $this->belongsTo(InventoryItem::class,'inventory_item_id');
    }

      public function treatments()
    {
        return $this->belongsTo(Treatment::class);
    }
}
