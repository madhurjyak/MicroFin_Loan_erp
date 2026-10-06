<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alters collection_transactions to support both loan repayments and savings deposits
 * in a unified ledger — required by CollectionLedgerService for CDS bulk settlements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_transactions', function (Blueprint $table) {
            // Make loan_id and schedule_id nullable (they'll be null for savings-only transactions)
            $table->unsignedBigInteger('loan_id')->nullable()->change();
            $table->unsignedBigInteger('schedule_id')->nullable()->change();

            // Add savings references
            $table->unsignedBigInteger('savings_account_id')->nullable()->after('schedule_id');
            $table->unsignedBigInteger('savings_schedule_id')->nullable()->after('savings_account_id');

            // Transaction type discriminator
            $table->enum('transaction_type', ['loan_repayment', 'savings_deposit', 'combined'])
                  ->default('loan_repayment')
                  ->after('savings_schedule_id');

            $table->foreign('savings_account_id')->references('id')->on('savings_accounts')->onDelete('set null');
            $table->foreign('savings_schedule_id')->references('id')->on('savings_schedules')->onDelete('set null');

            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::table('collection_transactions', function (Blueprint $table) {
            $table->dropForeign(['savings_account_id']);
            $table->dropForeign(['savings_schedule_id']);
            $table->dropColumn(['savings_account_id', 'savings_schedule_id', 'transaction_type']);
            $table->unsignedBigInteger('loan_id')->nullable(false)->change();
            $table->unsignedBigInteger('schedule_id')->nullable(false)->change();
        });
    }
};
