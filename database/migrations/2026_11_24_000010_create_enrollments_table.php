<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->foreignId('academic_year_id')->constrained('academic_years', 'academic_year_id');
            $table->foreignId('platoon_id')->constrained('platoons', 'platoon_id');
            $table->foreignId('rank_id')->constrained('ranks', 'rank_id');
            $table->foreignId('cadet_type_id')->constrained('cadet_types', 'cadet_type_id');
            $table->foreignId('cadet_id')->constrained('cadets', 'cadet_id');
            $table->unsignedTinyInteger('year_level');
            $table->string('enrollment_status')->default('active');
            $table->unique(['academic_year_id', 'cadet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
