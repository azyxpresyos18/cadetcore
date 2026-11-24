<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The calendar lets officers type a session type (Drill, Field Exercise, ...).
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->string('session_type', 40)->nullable()->after('venue');
        });

        // "Substitute officer mode" on the platoon pages keeps the substitute's name on the record.
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('substitute_officer')->nullable()->after('remarks');
        });
        Schema::table('merit_demerit_entries', function (Blueprint $table) {
            $table->string('substitute_officer')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', fn (Blueprint $t) => $t->dropColumn('session_type'));
        Schema::table('attendance_records', fn (Blueprint $t) => $t->dropColumn('substitute_officer'));
        Schema::table('merit_demerit_entries', fn (Blueprint $t) => $t->dropColumn('substitute_officer'));
    }
};
