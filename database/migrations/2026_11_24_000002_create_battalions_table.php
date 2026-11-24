<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battalions', function (Blueprint $table) {
            $table->id('battalion_id');
            $table->string('battalion_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battalions');
    }
};
