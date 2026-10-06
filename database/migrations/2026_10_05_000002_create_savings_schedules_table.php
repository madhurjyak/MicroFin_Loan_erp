<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_account_id')->constrained('savings_accounts')->onDelete('cascade');
            $table->unsignedInteger('installment_no');
            $table->date('due_date');
            $table->decimal('amount_expected', 10, 2);            // Expected deposit per period
            $table->decimal('amount_collected', 10, 2)->default(0);
            $table->decimal('interest_accrued', 10, 2)->default(0); // Interest on balance for this period
            $table->enum('status', ['pending', 'paid', 'missed', 'partial'])->default('pending');
            $table->date('collection_date')->nullable();
            $table->timestamps();

            $table->index(['savings_account_id', 'status']);
            $table->index('due_date');
            $table->unique(['savings_account_id', 'installment_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_schedules');
    }
};
