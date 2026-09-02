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
    {
        // REMOVE

        Schema::create('product_returns', function (Blueprint $table) {
         $table->foreignId('product_id')->constrained();
            $table->foreignId('returned_id')->constrained();
            $table->decimal('price',8,2)->unsigned()->default(0);
            $table->decimal('quantity',8,2)->unsigned()->default(0);

            $table->primary(['product_id','returned_id']);

            //price and quantity , maybe i will just use validation at this point, doing an
            //extra raw db will just cause too much query 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
