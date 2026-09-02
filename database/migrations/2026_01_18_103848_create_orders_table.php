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

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(false); // remove this
            $table->foreignId('branch_id')->constrained('branches');
            $table->index('branch_id','orders_branch_id_index');//even if its small its essential for faster query and filter
//even if its small its essential for faster query and filter go ingredient table and read example 


            $table->timestamps();
            $table->softDeletes();
            // =======
            // $table->foreignId('user_id')->constrained();
            // $table->foreignId('product_id')->constrained();
            // $table->integer('price');
            // >>>>>>> playground


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
