<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('medical_questionnaires', function (Blueprint $table) {
            $table->id();

            // One questionnaire per patient (updated when their health changes).
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();

            // {"pain_now": true, "nervous": false, ...}
            $table->json('answers');

            // ["asthma", "diabetes", ...] - the conditions the patient ticked
            $table->json('conditions');

            // "To the best of my knowledge, all of the preceding answers are true and correct."
            $table->boolean('agreed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_questionnaires');
    }
};