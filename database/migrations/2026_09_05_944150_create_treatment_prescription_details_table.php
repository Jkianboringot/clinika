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

    
            $table->foreignId('medication_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('dosage');
            $table->string('frequency');
            $table->unsignedInteger('duration_days');
            $table->text('special_instructions')->nullable();
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
