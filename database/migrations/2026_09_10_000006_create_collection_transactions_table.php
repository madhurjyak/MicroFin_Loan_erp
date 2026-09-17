<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('repayment_schedules')->onDelete('cascade');
            $table->string('receipt_no')->unique();
            $table->decimal('amount_collected', 12, 2);
            $table->date('collection_date');
            $table->string('collected_by');
            $table->enum('payment_mode', ['cash', 'upi_qr', 'nach', 'neft', 'rtgs'])->default('cash');
            $table->unsignedBigInteger('peer_payer_customer_id')->nullable(); // JLG peer payment
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('collection_date');
            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_transactions');
    }
};
