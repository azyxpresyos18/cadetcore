<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officers', function (Blueprint $table) {
            $table->foreignId('cadet_id')->primary()->constrained('cadets', 'cadet_id');
            $table->string('officer_serial_no')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officers');
    }
};
