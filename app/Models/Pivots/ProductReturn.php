<?php

namespace App\Models\Pivots;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
       protected $table = 'product_returns';
    
    protected $fillable = [
        'product_id',
        'returned_id',
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
