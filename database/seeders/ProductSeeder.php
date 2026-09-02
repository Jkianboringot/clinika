<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    // Product::factory(3)->create();
        $products = [
            // BREAD
            ['name' => 'Classic Bread', 'price' => 5.00, 'cost' => 2.00],
            ['name' => 'Premium Bread', 'price' => 5.00, 'cost' => 2.50],
            ['name' => 'Hotdog Bread', 'price' => 5.00, 'cost' => 2.00],
            ['name' => 'Hungarian Bread', 'price' => 5.00, 'cost' => 2.50],
            ['name' => 'Footlong Bread', 'price' => 5.00, 'cost' => 2.50],
 
            // PATTIES
            ['name' => 'JR. Classic Patty', 'price' => 12.00, 'cost' => 6.00],
            ['name' => 'JR. Hotdog', 'price' => 15.00, 'cost' => 7.50],
            ['name' => 'Classic Patty', 'price' => 15.00, 'cost' => 7.50],
            ['name' => 'Classic Hotdog', 'price' => 20.00, 'cost' => 10.00],
 
            // PREMIUM
            ['name' => 'Angus', 'price' => 30.00, 'cost' => 16.00],
            ['name' => 'Chicken', 'price' => 60.00, 'cost' => 32.00],
            ['name' => 'Hungarian', 'price' => 60.00, 'cost' => 32.00],
            ['name' => 'Footlong', 'price' => 60.00, 'cost' => 32.00],
            ['name' => 'Ham', 'price' => 43.00, 'cost' => 22.00],
 
            // DRINKS
            ['name' => 'Pepsi', 'price' => 22.50, 'cost' => 14.00],
            ['name' => 'Mt. Dew', 'price' => 15.00, 'cost' => 9.00],
            ['name' => 'Mirinda', 'price' => 15.00, 'cost' => 9.00],
            ['name' => 'Sting', 'price' => 15.00, 'cost' => 9.00],
            ['name' => 'C2 Solo', 'price' => 25.00, 'cost' => 16.00],
            ['name' => 'Bottled Water', 'price' => 25.00, 'cost' => 15.00],
 
            // ADD ONS
            ['name' => 'Egg', 'price' => 15.00, 'cost' => 7.00],
            ['name' => 'Cheese Slice', 'price' => 15.00, 'cost' => 8.00],
            ['name' => 'Cheese Cubes (Small)', 'price' => 10.00, 'cost' => 5.00],
            ['name' => 'Cheese Cubes (Big)', 'price' => 5.00, 'cost' => 2.50],
        ];
 
        foreach ($products as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                ['price' => $product['price'], 'cost' => $product['cost']]
            );
        }
 
        $this->command->info('Seeded ' . count($products) . ' products.');
    
        // $ingredients = Ingredient::pluck('id');
        // $products = Product::pluck('id');

        // foreach ($products as $productId) {

        //     // Shuffle and take 3–7 unique ingredients
        //     $usedIngredients = $ingredients->shuffle()->take(rand(3, 7));

        //     $pivotData = [];

        //     foreach ($usedIngredients as $ingredientId) {
        //         $pivotData[] = [
        //             'product_id' => $productId,
        //             'ingredient_id' => $ingredientId,
        //             'quantity' => rand(1, 5)
        //         ];
        //     }

        //     // Insert only this product's ingredients
        //     DB::table('product_ingredients')->insert($pivotData);
        // }
    }
}