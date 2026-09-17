<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->onDelete('cascade');
            $table->enum('document_type', [
                'aadhaar_card', 'pan_card', 'voter_id', 'bank_passbook', 'income_declaration'
            ]);
            $table->string('document_number')->nullable(); // Masked for Aadhaar: XXXX-XXXX-1234
            $table->string('file_path');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('loan_application_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_documents');
    }
};
