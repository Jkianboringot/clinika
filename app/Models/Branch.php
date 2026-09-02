<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
  protected $fillable = ['name','location', 'type_branch'];


  // public function ingredients(): BelongsToMany
  // {
  //   return $this->belongsToMany(Ingredient::class, 'ingredient_branchs');
  // }



  public function inventories(): HasMany
  {
    return $this->hasMany(Inventory::class);
  }
  

  
  public function reuturned(): HasMany
  {
    return $this->hasMany(Returned::class);
  }
  public function users(): HasMany
  {
    return $this->hasMany(User::class);
  }

  public function products(): BelongsToMany
  {
    return $this->belongsToMany(Product::class)
      ->withPivot('quantity');
  }

}
