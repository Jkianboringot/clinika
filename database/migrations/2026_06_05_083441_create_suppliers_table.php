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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            //just doing this to be more scalable, its not like its hard to do just do it, no harm done
            //this is just to prepare for future hr addition which am not supprise they will have
            $table->string('first_name', 75);
            $table->string('last_name', 75);
            $table->string('middle_name',75)->nullable();
            $table->string('contact',20)->nullable();
            $table->string('email',150)->nullable();
            $table->string('employee_code',10);
            $table->softDeletes();
            // INSIGHT
            // its good that i do this salary type here becuase only cashier will be hourly, i mean is only 
            // cashier will be consider in inventory, so we dont need other position thier so MB inventory,
            // will always be hourly and cashier
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
