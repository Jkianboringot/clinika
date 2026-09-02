<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReturnTypeWillChange;

class Order extends Model
{

    use SoftDeletes;
    protected $fillable = ['branch_id', 'status'];

    protected $casts = ['product_order'=>'integer'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_orders')
            ->withPivot(['quantity', 'price']);
    }

    public function returned(): Order
    {
        return $this->has(Returned::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
//     public function product(): BelongsTo{
//         return $this->belongsTo(Product::class);
// >>>>>>> playground
//     }

    //  public function user(): BelongsTo{
    //     return $this->belongsTo(User::class);
    // }


}
