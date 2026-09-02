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
        //REMOVE
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name',75);
              // $table->index('category_id','category_id_index');  too small , maybe expand in the future but fr now mb only has 10 category
                //this will stay index becuase i dont even think i need this shit, becuase in food stall for burger thier is only a few categry
                //meat,bread,sauce,softdrinks,accessocers that is all so i dont know if i even need category so for now
                // i will let this be a comment and in category i will make it not unique for now, becuase its unlike branch which will be use alot
                // in filter for , branch_order , for branch_return, branch_add_ingredient , this does not have that, but branch does, but this is mytake 
                // for now i need to learn mroe

            //rethink and justify
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
