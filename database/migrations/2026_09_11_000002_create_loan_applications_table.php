<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            $table->decimal('applied_amount', 12, 2);
            $table->decimal('annual_interest_rate', 5, 2)->default(22.00);
            $table->unsignedInteger('tenure');
            $table->enum('repayment_frequency', ['weekly', 'monthly'])->default('monthly');
            $table->string('purpose')->nullable();
            $table->enum('stage', ['draft', 'submitted', 'under_review', 'approved', 'rejected'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('stage');
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
