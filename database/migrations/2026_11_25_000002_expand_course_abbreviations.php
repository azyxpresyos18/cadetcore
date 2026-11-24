<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Officers created at setup were stored with an abbreviation (e.g. BSIT); cadets added through the form use the full course name. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (config('cadetcore.course_names', []) as $short => $full) {
            DB::table('cadets')->whereRaw('UPPER(course) = ?', [strtoupper($short)])->update(['course' => $full]);
        }
    }

    public function down(): void
    {
        // Not reversible: the original spelling of each record is not kept.
    }
};
