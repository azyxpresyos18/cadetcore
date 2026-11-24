<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_audiences', function (Blueprint $table) {
            $table->foreignId('session_id')->constrained('training_sessions', 'session_id');
            $table->foreignId('cadet_type_id')->constrained('cadet_types', 'cadet_type_id');
            $table->primary(['session_id', 'cadet_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_audiences');
    }
};
