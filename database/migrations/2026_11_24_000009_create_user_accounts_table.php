<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id('user_id');
            $table->foreignId('cadet_id')->unique()->constrained('cadets', 'cadet_id');
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
