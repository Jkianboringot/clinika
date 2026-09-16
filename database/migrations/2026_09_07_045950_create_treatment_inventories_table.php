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
        // this is what reduce the stock primarly
        Schema::create('treatment_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained();
            $table->foreignId('inventory_item_id')->constrained();
            $table->decimal('quanty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_inventories');
    }
};
