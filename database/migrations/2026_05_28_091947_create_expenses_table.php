<?php

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseType;
use App\Enums\PaymentMethod;
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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('amount');
            $table->string('expense_category',40)->default(ExpenseCategory::Branch->value);
            $table->string('expense_type',40)->default(ExpenseType::Utilities->value);
            $table->string('payment_method',40)->default(PaymentMethod::Cash->value);
            $table->text('description'); //what is the point of this
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
