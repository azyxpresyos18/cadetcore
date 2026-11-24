<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battalion_s1_assignments', function (Blueprint $table) {
            $table->foreignId('battalion_id')->constrained('battalions', 'battalion_id');
            $table->foreignId('academic_year_id')->constrained('academic_years', 'academic_year_id');
            $table->foreignId('user_id')->constrained('user_accounts', 'user_id');
            $table->primary(['battalion_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battalion_s1_assignments');
    }
};
