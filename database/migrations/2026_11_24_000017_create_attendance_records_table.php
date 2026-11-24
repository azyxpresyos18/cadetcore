<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->foreignId('session_id')->constrained('training_sessions', 'session_id');
            $table->foreignId('enrollment_id')->constrained('enrollments', 'enrollment_id');
            $table->foreignId('logged_by_user_id')->constrained('user_accounts', 'user_id');
            $table->string('attendance_status');
            $table->time('time_in')->nullable();
            $table->text('remarks')->nullable();
            $table->unique(['session_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
