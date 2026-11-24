<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corps_s1_assignments', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->primary()->constrained('academic_years', 'academic_year_id');
            $table->foreignId('user_id')->constrained('user_accounts', 'user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corps_s1_assignments');
    }
};
