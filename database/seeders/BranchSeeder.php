<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::insert([
            ['name' => 'mb1', 'location' => 'Calatagan', 'branch_type' => 'branch'],
            ['name' => 'mb2', 'location' => 'San Andres', 'branch_type' => 'franchise'],
            ['name' => 'mb3', 'location' => 'San Juan', 'branch_type' => 'center']
        ])
        ;
        $branches = Branch::all();
        $products = Product::all();

        // if ($branches->isEmpty() || $products->isEmpty()) {
        //     $this->command->warn('No branches or products found — seed those tables first.');

        // return;
        // }

        foreach ($branches as $branch) {
            $pivotData = [];

            foreach ($products as $product) {
                $pivotData[$product->id] = [
                    'quantity' => rand(10, 100), // starting stock — adjust as needed
                ];
            }

            $branch->products()->syncWithoutDetaching($pivotData);

            $this->command->info("Seeded {$products->count()} products for branch: {$branch->location}");
        }
    }
}
