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

    // ------------------------------------------------------------------
    // Low stock alert
    // ------------------------------------------------------------------
 
    /** Adds total_added / total_used columns so stock can be computed without N+1 queries. */
    public function scopeWithStockTotals(Builder $query): Builder
    {
        return $query
            ->withSum('inventoryStock as total_added', 'quantity')
            ->withSum('treatmentInventories as total_used', 'quantity');
    }
 
    /** Current stock. Uses the preloaded totals when available, otherwise falls back to stock(). */
    public function getCurrentStockAttribute(): float
    {
        if (array_key_exists('total_added', $this->attributes)) {
            return max(0, (float) $this->total_added - (float) $this->total_used);
        }
 
        return $this->stock();
    }
 
    /** Low stock = stock is LESS than the minimum stock (items without a minimum never alert). */
    public function isLowStock(): bool
    {
        return $this->minimum_stock !== null
            && $this->current_stock < (float) $this->minimum_stock;
    }
 
    /**
     * All items below their minimum stock, shaped for the React pages.
     * @return Collection<int, array{id:int,item_name:string,stock:float,minimum_stock:float}>
     */
    public static function lowStockItems(): Collection
    {
        return static::withStockTotals()
            ->whereNotNull('minimum_stock')
            ->orderBy('item_name')
            ->get()
            ->filter(fn (self $item) => $item->isLowStock())
            ->map(fn (self $item) => [
                'id'            => $item->id,
                'item_name'     => $item->item_name,
                'stock'         => $item->current_stock,
                'minimum_stock' => (float) $item->minimum_stock,
            ])
            ->values();
    }

}
