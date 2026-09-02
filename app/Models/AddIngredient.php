<?php

namespace App\Models;

use GuzzleHttp\Promise\Is;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddIngredient extends Model
{
  
  use HasFactory;
  protected $guarded = ['id', 'created_at', 'updated_at'];

  public function ingredients(): BelongsToMany
  {
    return $this->belongsToMany(Ingredient::class, 'add_to_ingredient')
      ->withPivot(['quantity']);
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  // public function add_ingredients(): BelongsToMany
  // {
  //     return $this->belongsToMany(AddIngredient::class, 'add_to_ingredient')
  //         ->withPivot(['quantity']);
  // }
}
