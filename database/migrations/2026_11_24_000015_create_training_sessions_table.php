<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->foreignId('academic_year_id')->constrained('academic_years', 'academic_year_id');
            $table->foreignId('posted_by_user_id')->constrained('user_accounts', 'user_id');
            $table->string('title');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
