<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
