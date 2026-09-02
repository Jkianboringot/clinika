<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
        // REMOVE
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained();
            $table->foreignId('ingredient_id')->constrained();
            
            $table->primary(['product_id','ingredient_id']);

            $table->integer('quantity')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
