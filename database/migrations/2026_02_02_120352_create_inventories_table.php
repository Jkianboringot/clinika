<?php

use App\Enums\InOutType;
use App\Enums\StockMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_type', 20)->default(InOutType::In->value);
            //TODO - ondelete
            //will put this on null for now , to show client shit
            $table->foreignId('encoder_id')->nullable()->constrained('users');
            $table->foreignId('branch_id')->nullable()->constrained();

            //WHY  Okay, I'm here to explain why I'm adding stock_movement_id to the inventory table. The way it would work is that we first create the stock movement so that we can get its ID, which we can then put into the inventory table.

            // Now, why is this bad? It's because of the way stock movements work. A stock movement holds the branch_id, product_id, and its quantity, so it is pretty much unique. For example, let's say a branch has 10 products. That would mean we have 10 stock movement records, one for each product.

            // Now I want you to imagine something similar with inventory records. Let's say we have a branch and we add five products. That would mean we need to have at least five inventory records, and the only difference between them would be the stock_movement_id. The inventory records are pretty much dependent on that ID alone. It is normalized, but it is probably not the best way.

            // Part of the whole point of normalization is to remove unnecessary repetition. However, there is also another problem, and that is that we need a way to know whether the updated stock is actually correct. A discrepancy can happen. It's not super common, but it is possible.

            // This brings us to the question of whether we should follow the pattern of simply updating the stock record, or keep creating movement records and calculate the total at the end. The bigger question is whether we should have a separate discrepancy record, or whether that information should be stored directly in the inventory table.

            // There could be discrepancies between what was actually added and what the stock says was added. It's very unlikely, but it could happen. That's why I think we should have a discrepancy record.

            // From my perspective, as of now, we probably shouldn't worry too much about discrepancies. We can handle that later and maybe create a separate table for it. That could actually be easier. We could simply record the ID of the inventory record where the discrepancy occurred.

            // As for the stock movement itself, if it's wrong, it could potentially be corrected or deleted.

            // From what I can see, honestly, the system as it currently stands might actually just need to change inventory or even ingredient into product
            // $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements');


            // TODO = make this enum
            $table->string('stock_movement_type', 40)->nullable()->default(StockMovementType::Transfer->value);
            // $table->index('stock_movement_id');
            //even if its small its e3Dssential for faster query and filter go ingredient table and read example 

            // ASK-YOURSELF - is this the over expected sale of the stock or just what was IN base on the stock IN it should be the whole
            //check the picture
            // $table->decimal('expected_sale',8,2)->unsigned()->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
