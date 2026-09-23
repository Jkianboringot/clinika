<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockAddition extends Model
{
    public $guarded = ['id'];


    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function items(): BelongsTo
    {
        // Column is "inventory_item_id", which doesn't match the
        // default guess ("items_id") Laravel would make from the
        // relation method name, so it must be passed explicitly.
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
