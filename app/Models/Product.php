<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

  use HasFactory;

  public $fillable = ['name', 'price'];

  // protected $fillable = ['name','unit_cost','description','status','category_id','is_active'];


  // protected $casts = ['status' => ProductStatusEnum::class];

  public function orders()
  {
    return $this->belongsToMany(Order::class, 'product_orders')
      ->withPivot(['quantity', 'price']);
  }


  public function returneds()
  {
    return $this->belongsToMany(Order::class, 'product_returns')
      ->withPivot(['quantity', 'price']);
  }

  public function ingredients(): BelongsToMany
  {
    return $this->belongsToMany(Ingredient::class, 'product_ingredients')
      ->withPivot(['quantity']);
    ;
  } //might cause error, i feel it




  // i just need to figure out who can we access ingredient stock in product, take one step at the time

  // ok i cannot access the pivot table of ingredient , i think the only way is to create a model for it


  public function productStock()
  {
    return Product::join('product_ingredients', 'product.id', '=', 'product_ingredients.product_id');
  }


  /*
i think i need to use case for this to work
first we need to access product join that with product ingedient, then check if this still has stock, but
that think about this is, we need to knw the stock of ingedient, so e becuase product_ingredient does not really tell me
anything about stock it just tells me the connection between product and ingredient, and i cannot really use return adn order
here because that would mean am jsut recreating what is in stock, so either i do this or we can access the method
in ingedient stock and put it here,

i know we ahve to use case sql for this, lets ignore this for now, and make the cashier side, mostly the livewire controller 
and view, then figure this out, also viszualize what you did in ingredient to the tablet

*/







  public function branches(): BelongsToMany
  {
    return $this->belongsToMany(Branch::class)->withPivot('quantity');
  }





  //playground
  // public function category(): BelongsTo{ //in filament it needs to be strict type, if or has a type in fucntion, if not it will break
  //   return $this->belongsTo(Category::class);
  // }

  // public function tags(): BelongsToMany{
  //   return $this->belongsToMany(Tag::class);
  // }


  public function expectedSale()
  {
    // FIXME - not done or tested, this will show the expected sale of whole inventory
    return $this->branch()->quantity + $this->product->price;
  }




}
