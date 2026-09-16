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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('treatment_prescription_id')->constrained('treatment_prescription_details');
            $table->string('follow_up_schedule');
            
            $table->foreignId('appointment_id')
                ->constrained()
                ->cascadeOnDelete();
            
            
            $table->string('tooth_numbers')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            // summary of what was done
            $table->text('summary')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
