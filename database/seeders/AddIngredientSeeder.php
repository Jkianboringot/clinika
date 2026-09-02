<?php

namespace Database\Seeders;

use App\Models\AddIngredient;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddIngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AddIngredient::factory()->count(3)->create();
        $ingredients = Ingredient::pluck('id');
        $addIngredients = AddIngredient::pluck('id');

        $pivotData = [];

        foreach ($addIngredients as $addIngredientId) {

            // each product uses 3–7 ingredients
            $usedIngredients = $ingredients->random(rand(3, 7));

            foreach ($usedIngredients as $ingredientId) {

                $pivotData[] = [
                    'product_id' => $addIngredientId,
                    'ingredient_id' => $ingredientId,
                    'quantity' => rand(1, 5)
                ];
            }
            DB::table('product_ingredients')->insert($pivotData);
        }
    }
}
