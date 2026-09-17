<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date');
            $table->decimal('principal_due', 12, 2)->default(0);
            $table->decimal('interest_due', 12, 2)->default(0);
            $table->decimal('penal_charges_due', 10, 2)->default(0);
            $table->decimal('penal_gst_due', 10, 2)->default(0);    // 18% GST on penal
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('penal_paid', 10, 2)->default(0);
            $table->decimal('gst_paid', 10, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->timestamps();

            $table->index(['loan_id', 'due_date']);
            $table->index(['loan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayment_schedules');
    }
};
