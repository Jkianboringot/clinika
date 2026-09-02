<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::insert([
            ['user_id' => 1, 'product_id' => 1,'price'=>432],
            ['user_id' => 1, 'product_id' => 2,'price'=>432],
            ['user_id' => 1, 'product_id' => 3,'price'=>432]
        ]);
    }
}
