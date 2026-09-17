<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ots_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->decimal('total_outstanding', 12, 2);
            $table->decimal('proposed_amount', 12, 2);
            $table->decimal('waiver_penal_gst', 10, 2)->default(0);
            $table->decimal('waiver_interest', 12, 2)->default(0);
            $table->decimal('waiver_principal', 12, 2)->default(0);
            $table->decimal('haircut_pct', 5, 2)->default(0);  // total waiver as % of outstanding
            $table->enum('approval_authority', [
                'Branch_Manager', 'Regional_Credit_Committee', 'Board'
            ])->default('Branch_Manager');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ots_proposals');
    }
};
