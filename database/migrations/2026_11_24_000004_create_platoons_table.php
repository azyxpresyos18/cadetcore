<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platoons', function (Blueprint $table) {
            $table->id('platoon_id');
            $table->foreignId('company_id')->constrained('companies', 'company_id');
            $table->string('platoon_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platoons');
    }
};
