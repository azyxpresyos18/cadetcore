<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platoon_leader_assignments', function (Blueprint $table) {
            $table->foreignId('platoon_id')->constrained('platoons', 'platoon_id');
            $table->foreignId('academic_year_id')->constrained('academic_years', 'academic_year_id');
            $table->foreignId('user_id')->constrained('user_accounts', 'user_id');
            $table->primary(['platoon_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platoon_leader_assignments');
    }
};
