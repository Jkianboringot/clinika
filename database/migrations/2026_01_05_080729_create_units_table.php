<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // This will be used to calculate ingredients stock since ingredients are tracked by unit(pack).
        // For example, a pack of patties is sent to a branch. Each pack contains 12 patties,
        // and if 10 packs are sent, the branch should technically have 120 patties in total.
        //
        // This will also be used when calculating the expected inventory value and adding stock. However, we
        // need to clarify how the cashier reports inventory back to the encoder.
        //
        // For example, if one pack is opened and 10 patties are used, there would be
        // 110 patties remaining. If the cashier reports "110 patties left," then inventory
        // is being reported per piece, not per pack.
        //
        // We need to determine which method they use because we cannot support both at the
        // same time. Inventory must be tracked either per piece or per pack.
        //
        // If inventory is tracked per pack, they would report having only 9 packs left
        // because the 10th pack has already been opened. If this is the case, the
        // calculation and inventory management will be simpler.

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 75);
            $table->decimal('pieces_per_unit',8,2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
