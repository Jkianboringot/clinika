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
        Schema::create('supplier_payments', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('supplier_ledger_id')->constrained();
            $table->decimal('amount_paid')->unsigned();
            //we will just use the updated_at as the last payment column, no we cannot becuase this can be edited
            $table->date('last_payment_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
