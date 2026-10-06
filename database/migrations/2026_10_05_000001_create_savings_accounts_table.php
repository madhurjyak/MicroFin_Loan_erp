<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('account_no')->unique();               // e.g. RD20260001
            $table->enum('account_type', ['rd', 'fd'])->default('rd'); // Recurring Deposit / Fixed Deposit
            $table->decimal('deposit_amount', 10, 2);             // Fixed periodic instalment amount
            $table->decimal('interest_rate', 5, 2)->default(5.00); // Annual %
            $table->unsignedInteger('tenure');                    // In months
            $table->enum('frequency', ['weekly', 'monthly'])->default('weekly');
            $table->date('opening_date');
            $table->date('maturity_date')->nullable();
            $table->enum('status', ['active', 'closed', 'matured', 'premature_closed'])->default('active');
            $table->decimal('total_principal_collected', 12, 2)->default(0);
            $table->decimal('total_interest_accrued', 12, 2)->default(0);
            $table->decimal('maturity_amount', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
