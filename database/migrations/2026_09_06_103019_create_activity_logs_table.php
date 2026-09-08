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
        Schema::create('activity_logs', function (Blueprint $table) {
             $table->id();

            // User who performed the action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // e.g. Recorded treatment, Updated inventory
            $table->string('action');

            // Human-readable explanation of what happened
            $table->text('details')->nullable();

            // e.g. Treatment, Inventory, Appointment, User, System
            $table->string('type');

            $table->timestamps();

            $table->index('type');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
