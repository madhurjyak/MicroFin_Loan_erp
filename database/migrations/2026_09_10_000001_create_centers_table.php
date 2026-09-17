<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('center_name');
            $table->string('center_code')->unique();
            $table->string('branch_name');
            $table->enum('meeting_day', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']);
            $table->time('meeting_time')->default('10:00:00');
            $table->string('field_officer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
