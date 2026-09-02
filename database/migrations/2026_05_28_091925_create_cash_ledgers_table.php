<?php

use App\Enums\PaymentMethod;
use App\Enums\CashLedgerType;
use App\Enums\InOutType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('ledger_type', 20)->default(InOutType::In->value);
            $table->string('category', 150);
            $table->decimal('amount')->unsigned();
            $table->string('payment_method',40)->default(PaymentMethod::Cash->value);
            $table->string('reference_no', 150);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_ledgers');
    }
};
