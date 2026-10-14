<?php

use App\Enums\GenderEnum;
use App\Enums\IsActiveEnum;
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
            $table->string('first_name',100);
            $table->string('last_name',100);
                 $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->string('gender',25);
            $table->string('contact_number',25)->nullable();
            $table->text('address')->nullable(); //no need for seperate table since its unique for user as it is complete address if it was just barangay or something that repeat then ywah, seperate it with another table

            //honestly i want it seperate table but dont need to seperate this since i dont care or it was specify that it needs to have like severity not my problem, just follow the shit done make it harder than it needs to be
            $table->text('medical_history')->nullable();
            $table->text('allergies')->nullable();
            $table->string('is_active')->default(IsActiveEnum::ACTIVE->value);

            //all of this are prime target for seperate table but how often do people here will honestly use this system, if alot then i would take it seroustly but its fucking capstone project so probably not
            $table->string('emergency_contact_number',25)->nullable();
            $table->string('emergency_contact_name',100)->nullable();

            // Account Information
            $table->string('email',150)->unique();
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
