<?php

use App\Enums\EmployeePosition;
use App\Enums\EmployeeSalaryType;
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
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('employee_code',20);
                //just doing this to be more scalable, its not like its hard to do just do it, no harm done
                //this is just to prepare for future hr addition which am not supprise they will have
                $table->string('first_name',75);
                $table->string('last_name',75);
                $table->string('middle_name')->nullable();
                $table->string('position',40)->default(EmployeePosition::Cashier->value);
                $table->string('salary_type',40)->default(EmployeeSalaryType::Hourly->value);
                $table->softDeletes();
                // INSIGHT
                // its good that i do this salary type here becuase only cashier will be hourly, i mean is only 
                // cashier will be consider in inventory, so we dont need other position thier so MB inventory,
                // will always be hourly and cashier
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
