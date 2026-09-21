<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    // this needs to be a seperate table since one persson can be prescripted for more the one medicine
    public function up(): void
    {
        Schema::create('treatment_prescription_details', function (Blueprint $table) {
            $table->id();
            // TODO: make this primary but not now since aivon will have issues and its development still so i dont give a shit
            $table->foreignId('treatment_id')->constrained(); 
    
            $table->foreignId('medication_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('dosage');
            $table->string('frequency');
            $table->unsignedInteger('duration_days');

            // note or special instruction for patient
            $table->text('dentist_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_prescription_details');
    }
};
