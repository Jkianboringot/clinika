<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pest\Concerns\Retrievable;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'inventory_id',
        'shift',
        'cash_shortage',
        'cash_amount',
        'gcash_amount',
        'cash_advance',
        'remitted_expenses',
        'net_cash'
    ];
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function encoder()
    {
        return $this->belongsTo(User::class,'encoder_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    protected function totalSale(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->gcash_amount ?? 0) + ($this->cash_amount ?? 0)
        );
    }
}
