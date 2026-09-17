<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recovery_case_id')->constrained('recovery_cases')->onDelete('cascade');
            $table->enum('interaction_type', ['telecalling', 'field_visit', 'email', 'whatsapp'])->default('telecalling');
            $table->dateTime('contact_time'); // must be between 08:00 and 19:00
            $table->enum('disposition', [
                'PTP', 'Broken_PTP', 'Dispute', 'Absconding',
                'Crop_Failure', 'Medical_Emergency', 'RNR', 'Paid'
            ])->nullable();
            $table->date('ptp_date')->nullable();     // Promise to Pay date
            $table->decimal('ptp_amount', 10, 2)->nullable();
            $table->string('logged_by');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('recovery_case_id');
            $table->index('contact_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_call_logs');
    }
};
