<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merit_demerit_entries', function (Blueprint $table) {
            $table->id('entry_id');
            $table->foreignId('enrollment_id')->constrained('enrollments', 'enrollment_id');
            $table->foreignId('logged_by_user_id')->constrained('user_accounts', 'user_id');
            $table->date('entry_date');
            $table->string('entry_type');
            $table->unsignedSmallInteger('points');
            $table->text('reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merit_demerit_entries');
    }
};
