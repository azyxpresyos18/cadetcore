<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadet_types', function (Blueprint $table) {
            $table->id('cadet_type_id');
            $table->string('type_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadet_types');
    }
};
