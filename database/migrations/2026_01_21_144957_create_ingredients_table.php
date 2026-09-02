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

        // TODO rename this to raw_material
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 75);
            $table->integer('threshold')->unsigned()->default(10);
            $table->integer('selling_price')->unsigned()->default(10);
            $table->integer('cost')->unsigned()->default(10);
            //this feels ilegal in some way, i dont think that the best but it will do for now since 
            //it will apply even if i did not use eloquent
            $table->foreignId('unit_id')->nullable()->constrained();


            //REMOVE - what is this index, its it should be category_id because its obvuis, index can be 
            // design later, but fro now just index the obvios one
            $table->index('name', 'ingredient_name_index');
            // $table->index('category_id','category_id_index');  too small , maybe expand in the future but fr now mb only has 10 category
            //this will stay index becuase i dont even think i need this shit, becuase in food stall for burger thier is only a few categry
            //meat,bread,sauce,softdrinks,accessocers that is all so i dont know if i even need category so for now
            // i will let this be a comment and in category i will make it not unique for now, becuase its unlike branch which will be use alot
            // in filter for , branch_order , for branch_return, branch_add_ingredient , this does not have that, but branch does, but this is mytake 
            // for now i need to learn mroe














            //lets do it, i will use this actually in filter, but it depends on how much is category if it 
            //exceed 100 record then this is fine,

            //justify this later, if its not good i will just remove it before prod

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
