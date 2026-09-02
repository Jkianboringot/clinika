<?php
namespace App\Models\Pivots;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductOrder extends Pivot
{
    protected $table = 'product_orders';
    
    protected $fillable = [
        'product_id',
        'order_id',
        'quantity',
        'price'
    ];
    
    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
    ];

       public function product()
    {
        return $this->belongsTo(Product::class);
    }
}