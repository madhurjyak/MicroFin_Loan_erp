<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->unsignedInteger('dpd')->default(0); // Days Past Due
            $table->enum('asset_classification', [
                'Standard', 'SMA-0', 'SMA-1', 'SMA-2', 'NPA_SubStandard', 'Doubtful'
            ])->default('Standard');
            $table->decimal('total_overdue_principal', 12, 2)->default(0);
            $table->decimal('total_overdue_interest', 12, 2)->default(0);
            $table->decimal('total_penal_charges', 10, 2)->default(0);
            $table->decimal('total_outstanding', 12, 2)->default(0);
            $table->string('assigned_officer')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique('loan_id');
            $table->index('asset_classification');
            $table->index('dpd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_cases');
    }
};
