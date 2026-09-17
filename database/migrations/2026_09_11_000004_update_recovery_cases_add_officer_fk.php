<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->foreignId('assigned_officer_id')
                  ->nullable()
                  ->after('assigned_officer')
                  ->constrained('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_officer_id']);
            $table->dropColumn('assigned_officer_id');
        });
    }
};
