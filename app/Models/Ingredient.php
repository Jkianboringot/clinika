<?php

namespace App\Models;

use App\Models\Pivots\ProductIngredient;
use App\Models\Pivots\ProductOrder;
use App\Models\Pivots\ProductReturn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'threshold',
        'category_id',
        'unit_id',
        'unit_quantity'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot(['quantity']);
    }


    public function addIngredients(): BelongsToMany
    {
        return $this->belongsToMany(AddIngredient::class, 'add_to_ingredient')
            ->withPivot(['quantity']);
    }

    public function sold(): int
    {
        // 1. product_order->product->ingredient_product 
        return ProductOrder::join('products', 'product_orders.product_id', '=', "products.id")
            ->join('product_ingredients', 'products.id', '=', 'product_ingredients.product_id')
            ->where('product_ingredients.ingredient_id', $this->id)
            ->selectRaw('SUM(product_ingredients.quantity * product_orders.quantity) as total')
            ->value('total') ?? 0;


        // this is still wrong, because what if we sole 2 burger that would mean 2patty, and 2buns will be decrease
        //but with this it will not do that, i will just decrease it by one everytime, just accounting for a record being create
        //what i need to do i times it by product_order.quantity, adn the product_ingredeint.ingredient_id
        // ->sum('product_ingredients.quantity');


        //i was missing hte where, i thougth of doing it by i dont really know what id to point to, thats why i ruled out doing where
        //becuase i just op to how relation like addIngredient work, but that is not possible since productOrder is not a relation
        //but $this->id solve that, since we are in Ingredient model we can do $this->id becuase that model has an automatic $this-id
        //that give you the id of an instance , and this i did not know , so maybe read about it, read more laravel docs, you need more 
        //knowledge in laravel to be able to create a more solid plan in the future





        // // dd(ProductOrder::join('products','product_orders.product_id','=',"products.id")
        // //         ->join('product_ingredients','products.id','=','product_ingredients.product_id')->sum('product_ingredients.quantity')
        // // )
        // // ;
        //         // 2. product->ingredient_product
        //             // -this is not good, everytime we add a new product it decrease in ingredient, this the job of order
        //         // return Product::join('product_ingredients', 'products.id', '=', "product_ingredients.product_id")->sum('product_ingredients.quantity');


    }



    public function returns(): int
    {
        //why was i joining with return, i dont need that all i need is product_return
        return ProductReturn::join('products', 'product_returns.product_id', '=', 'products.id')
            ->join('product_ingredients', 'products.id', '=', 'product_ingredients.product_id')
            // the point of joining product_ingredient to product is so that we can access product_ingredient ingredient, and this can only be done
            // by product, since we want it to point to only one product, and get all of the connecting ingredient into it
            ->where('product_ingredients.ingredient_id', $this->id)
            //we find the ingredient that was in product_ingredient of the product that we choice, and since this is the INgredient 
            //model we can use $this->id which point to each indivual ingredient
            ->selectRaw('SUM(product_ingredients.quantity * product_returns.quantity) as total')
            ->value('total') ?? 0;
    }

    public function getIngredientStockAttribute(): int
    {
        return ($this->addIngredients()->sum('add_to_ingredient.quantity') ?? 0)
            + $this->returns()
            - $this->sold();

        // ($this->products()->sum('product_ingredients.quantity') ?? 0)
        // this one work and decrease the ingredient that is only connected to product, but its wrong, because it decrease
        // the ingredient everytime a product is created



        /*  this is respoble for increase and decreasing stock by use ORM:
        here is raw sql
        //  SELECT SUM(add_to_ingredient.quantity) AS total_quantity
        // FROM ingredients
        // INNER JOIN add_to_ingredient
        //     ON ingredients.id = add_to_ingredient.ingredient_id
         WHERE add_to_ingredient.parent_id = 5;

            this pretty much just take the sum of all ingredient quantity  in pivot , 
            then it it either add or decrease

            this is a visualize docs in tablet, i nreallly dont fully understand this yet
*/
    }

    function getBranchIngredientStockAttribute()
    {
        return ($this->addIngredients()->where('branch_id',Auth::user()->branch_id)->value('add_to_ingredient.quantity') ?? 0)
            + $this->returns()
            - $this->sold();

            // ok this is whats being shown but check if its actually valid or not
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
