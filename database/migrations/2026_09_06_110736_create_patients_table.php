<?php

use App\Enums\GenderEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            //just doing this to be more scalable, its not like its hard to do just do it, no harm done
            //this is just to prepare for future hr addition which am not supprise they will have
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('gender')->default(GenderEnum::OTHER->value);
            $table->string('contact_number')->nullable();
            $table->text('address')->nullable(); //no need for seperate table since its unique for user as it is complete address if it was just barangay or something that repeat then ywah, seperate it with another table

            //honestly i want it seperate table but dont need to seperate this since i dont care or it was specify that it needs to have like severity not my problem, just follow the shit done make it harder than it needs to be
            $table->text('medical_history')->nullable();
            $table->text('allergies')->nullable();

            //all of this are prime target for seperate table but how often do people here will honestly use this system, if alot then i would take it seroustly but its fucking capstone project so probably not
            $table->string('emergency_contact_number');
            $table->string('emergency_contact_name');

            // Account Information
            $table->string('email')->unique();
            $table->string('password');

            // $table->softDeletes();, 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
