<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->unsignedBigInteger('loan_application_id')->nullable();
            $table->string('loan_account_no')->unique();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('annual_interest_rate', 5, 2)->default(22.00);
            $table->unsignedInteger('tenure');
            $table->enum('repayment_frequency', ['weekly', 'monthly'])->default('monthly');
            $table->enum('interest_type', ['reducing', 'flat'])->default('reducing');
            $table->date('disbursement_date');
            $table->date('maturity_date')->nullable();
            $table->enum('status', ['active', 'closed', 'npa', 'written_off'])->default('active');
            $table->string('disbursement_mode')->default('cash');
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->decimal('processing_fee_gst', 10, 2)->default(0);
            $table->string('purpose')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
