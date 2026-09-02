<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
  use HasFactory;
      protected $fillable = ['name'];

      public function ingredients(): HasMany{
        return $this->hasMany(Ingredient::class);
      }//one to many


      //playground
    // public $guarded = ['id'];


    // public function products(): HasMany{
    //     return $this->hasMany(Product::class);
    // }
}
