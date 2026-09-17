<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->string('customer_code')->unique();
            $table->string('full_name');
            $table->string('phone', 10);
            $table->string('pan_number', 10)->nullable();
            $table->string('aadhaar_last4', 4);
            $table->text('address');
            $table->string('district');
            $table->string('state');
            $table->string('pincode', 6);
            $table->decimal('annual_household_income', 12, 2)->default(0);
            $table->decimal('monthly_debt_obligations', 12, 2)->default(0);
            $table->string('bank_account_no')->nullable();
            $table->string('ifsc_code', 11)->nullable();
            $table->enum('gender', ['Female','Male','Other'])->default('Female');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
