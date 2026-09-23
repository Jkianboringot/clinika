<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;
    public $guarded = ['id'];


    public function treatmentInventories()
    {
        return $this->hasMany(TreatmentInventory::class);
    }

    function inventoryStock()
    {
        return $this->hasMany(InventoryStockAddition::class);
    }

  function stock(): float
    {
        $treatmentTotal = $this->treatmentInventories()->sum('treatment_inventories.quantity') ?? 0;
        $stockAdditionTotal = $this->inventoryStock()->sum('inventory_stock_additions.quantity') ?? 0;
 
         return max(0, $stockAdditionTotal - $treatmentTotal);
    }

}
