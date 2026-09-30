<?php

namespace App\Models;

use App\Enums\StockStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class InventoryItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function treatmentInventories()
    {
        return $this->hasMany(TreatmentInventory::class);
    }

    public function inventoryStock()
    {
        return $this->hasMany(InventoryStockAddition::class);
    }

    public function stock(): float
    {
        $used  = (float) $this->treatmentInventories()->sum('quantity');
        $added = (float) $this->inventoryStock()->sum('quantity');

        return max(0, $added - $used);
    }

    // ------------------------------------------------------------------
    // Stock + status (PHP side, for display)
    // ------------------------------------------------------------------

    /** Adds total_added / total_used so stock can be computed without N+1 queries. */
    public function scopeWithStockTotals(Builder $query): Builder
    {
        return $query
            ->withSum('inventoryStock as total_added', 'quantity')
            ->withSum('treatmentInventories as total_used', 'quantity');
    }

    public function getCurrentStockAttribute(): float
    {
        if (array_key_exists('total_added', $this->attributes)) {
            return max(0, (float) $this->total_added - (float) $this->total_used);
        }

        return $this->stock();
    }

    public function getStockStatusAttribute(): StockStatusEnum
    {
        return StockStatusEnum::fromStock(
            $this->current_stock,
            $this->minimum_stock !== null ? (float) $this->minimum_stock : null,
        );
    }

    public function isLowStock(): bool
    {
        return $this->stock_status !== StockStatusEnum::InStock;
    }

    // ------------------------------------------------------------------
    // Stock status (SQL side, for queries)
    // ------------------------------------------------------------------

    /** Correlated subquery for current stock. Assumes FK is inventory_item_id on both tables. */
    protected static function stockSql(): string
    {
        return '(COALESCE((SELECT SUM(isa.quantity) FROM inventory_stock_additions isa
                            WHERE isa.inventory_item_id = inventory_items.id), 0)
               - COALESCE((SELECT SUM(ti.quantity) FROM treatment_inventories ti
                            WHERE ti.inventory_item_id = inventory_items.id), 0))';
    }

    /** Low OR critical: stock below minimum. Items without a minimum never match. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->whereNotNull('minimum_stock')
            ->whereRaw(static::stockSql() . ' < minimum_stock');
    }

    public function scopeWhereStockStatus(Builder $query, StockStatusEnum $status): Builder
    {
        $stock = static::stockSql();

        return match ($status) {
            StockStatusEnum::Critical => $query
                ->whereNotNull('minimum_stock')
                ->whereRaw("$stock < minimum_stock")
                ->whereRaw("$stock <= minimum_stock * ?", [StockStatusEnum::CRITICAL_RATIO]),

            StockStatusEnum::Low => $query
                ->whereNotNull('minimum_stock')
                ->whereRaw("$stock < minimum_stock")
                ->whereRaw("$stock > minimum_stock * ?", [StockStatusEnum::CRITICAL_RATIO]),

            StockStatusEnum::InStock => $query->where(
                fn (Builder $q) => $q->whereNull('minimum_stock')
                    ->orWhereRaw("$stock >= minimum_stock")
            ),
        };
    }

    /**
     * All items below their minimum stock, shaped for the React pages.
     * @return Collection<int, array{id:int,item_name:string,stock:float,minimum_stock:float,status:string,status_label:string}>
     */
    public static function lowStockItems(): Collection
    {
        return static::withStockTotals()
            ->lowStock()
            ->orderBy('item_name')
            ->get()
            ->map(fn (self $item) => [
                'id'            => $item->id,
                'item_name'     => $item->item_name,
                'stock'         => $item->current_stock,
                'minimum_stock' => (float) $item->minimum_stock,
                'status'        => $item->stock_status->value,
                'status_label'  => $item->stock_status->label(),
            ])
            ->values();
    }
}