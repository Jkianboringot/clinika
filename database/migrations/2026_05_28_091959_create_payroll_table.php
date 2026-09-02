<?php

use App\Enums\PaidStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        //PROBLEM:
            // how are we gonna treat diffreent salary type, hour needs to be store because same guy can work
            //4 hours monday and tuesday he work only 2hours, and in daily its more like attendance
        //SOLUTION -just do this in application layer , by doing pattern two how i dont know 
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            // $table->foreignId('branch_id')->constrained(); //i dont know why i add this here but remove its in notes i explain it why am removing it
            //REVIEW
            //just do gross_pay - deduct to get net pay, we dont
            // need to put net pay here since it can be derived to this two
            $table->decimal('gross_pay', 8, 2)->unsigned();
            $table->decimal('deduct', 8, 2)->unsigned();
            $table->string('paid_status', 40)->default(PaidStatus::Unpaid->value);
            // $table->enum()

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
