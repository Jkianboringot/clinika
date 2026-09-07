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
        Schema::create('patient_medical_infos', function (Blueprint $table) {
            $table->foreignId('patient_id')->constrained();
            //honestly i want it seperate table but dont need to seperate this since i dont care or it was specify that it needs to have like severity not my problem, just follow the shit done make it harder than it needs to be
            $table->text('medical_history')->nullable();
            $table->text('allergies')->nullable();
            $table->primary('patient_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_infos');
    }
};
