<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->enum('notice_type', [
                'Sec_138_NI_Act',
                'Sec_25_PSSA_AutoDebit_Bounce',
                'Loan_Recall_Notice',
                'SARFAESI_13_2',
            ]);
            $table->string('notice_ref_no')->unique();
            $table->date('dispatch_date');
            $table->string('tracking_speedpost_no')->nullable();
            $table->enum('status', [
                'generated', 'dispatched', 'served', 'escalated_to_lok_adalat', 'court_filed'
            ])->default('generated');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_notices');
    }
};
