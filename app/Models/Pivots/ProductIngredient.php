<?php

namespace App\Models\Pivots;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class ProductIngredient extends Model
{
    protected $table = 'product_ingredients';
    
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity'
    ];
    
   
       public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
