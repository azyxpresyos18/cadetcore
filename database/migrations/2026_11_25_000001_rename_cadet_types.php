<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Existing databases were set up with "Basic" / "Advanced"; the cadet types are now "Regular Cadet" / "Aspiring Cadet". */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('cadet_types')->where('type_name', 'Basic')->update(['type_name' => 'Regular Cadet']);
        DB::table('cadet_types')->where('type_name', 'Advanced')->update(['type_name' => 'Aspiring Cadet']);
    }

    public function down(): void
    {
        DB::table('cadet_types')->where('type_name', 'Regular Cadet')->update(['type_name' => 'Basic']);
        DB::table('cadet_types')->where('type_name', 'Aspiring Cadet')->update(['type_name' => 'Advanced']);
    }
};
