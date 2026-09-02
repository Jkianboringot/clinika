<?php

use App\Enums\Shift;
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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();//already index, we only create another index if            
            //we are doing a where with branch shift for example, if we are doing that alot, not speculation

            //NEWLY-ADDED - i added unique because i want one inventory to have one sale, but am total sure about this in the future
            //but as of now i am
            $table->foreignId('inventory_id')->unique()->constrained();//1:1 with inventory, not done yet

            //INSIGHT
            // i think we can just derive this shift from payout/
            // SOLUTION no keep shift here it is correct hte point is to track sale for each shiftf
            $table->string('shift',40)->default(Shift::Opening->value);
            $table->decimal('cash_amount',8,2)->unsigned();
            $table->decimal('cash_advance',8,2)->unsigned()->nullable();

            $table->decimal('cash_shortage',8,2)->unsigned()->nullable();
            $table->decimal('remitted_expenses',8,2)->unsigned()->nullable();
            $table->decimal('gcash_amount',8,2)->unsigned()->nullable();
            
            
            // ASK-YOURSELF - is this a good idea this is can honestly be derive by the 
            // 4 column above this, so its not heavy but if thier is alot of that it is heavy
            // , so am asking myself is better to just have one extra column that i can use in overall cash too 
            // and quick read or just auto calcualte it, becuase am planning to have one table for the driff it pretty 
            // much calculate the sale and save it in the table and in that table we read it thier
            $table->decimal('net_cash',8,2)->unsigned();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
