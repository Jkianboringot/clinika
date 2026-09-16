<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AppointmentStatusEnum;
use AppointmentStatusEnum as GlobalAppointmentStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users');
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('procedure_id')->constrained();
            $table->text('description')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
        // TODO(later) bring back later after am done with user
            // $table->string('status')->default(GlobalAppointmentStatusEnum::InProgress->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
