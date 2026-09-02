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
        // if this is table it just mean extension of another table, not new table, like create
        Schema::create('branch_product', function (Blueprint $table) {

            // WHY we keep this is because we can use it later on for the drift
            $table->id();

            
            // TODO what is this still doing here i thought i was change, yeah stoping this was a mistake haha
            // $table->foreignId('add_ingredient_id')->constrained();

            //REMOVE this
            // $table->foreignId('ingredient_id')->constrained();

            // WHY was this primary
            // $table->primary(['add_ingredient_id', 'ingredient_id']);
            $table->integer('quantity')->unsigned();

            // NEWLY-ADDED - just added this becuase unique needs it
            $table->foreignId('branch_id')->constrained();

            //STUDY
            $table->foreignId('product_id')->constrained();



            $table->unique(['branch_id', 'product_id']);
            //INSIGHT - this insure that each add_to_ingredient dont repeat
            //so product:1, branch:1, is unique for on record it means branch 
            //only has one product with id of one it does not duplicate, also 
            //when doing thing we need to hink about order because if it is query
            //or join the place ment of branch_id or product_id will depend on what 
            //we use to quer if we use branch to get hte product from it then we need it 
            // first so that its faster ,if we use product then we need product id first, 
            //search it up to leanr more, but this is for inedx not unique, and this can only 
            // really be decided when we already have query not now

            //TODO- learn more about index types its pretty good for query speed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_product');
    }
};
