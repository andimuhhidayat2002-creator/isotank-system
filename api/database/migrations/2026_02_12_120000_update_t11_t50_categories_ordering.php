<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update T11 Categories with A-E Prefixes
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'Front Out Side View')
            ->update(['category' => 'A. Front Out Side View']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'Rear Out Side View')
            ->update(['category' => 'B. Rear Out Side View']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'Right Side')
            ->update(['category' => 'C. Right Side']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'Left Side')
            ->update(['category' => 'D. Left Side']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'Top')
            ->update(['category' => 'E. Top']);

        // 2. Update T50 Categories with A-E Prefixes
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->where('category', 'Front Out Side View')
            ->update(['category' => 'A. Front Out Side View']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->where('category', 'Rear Out Side View')
            ->update(['category' => 'B. Rear Out Side View']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->where('category', 'Right Side/Valve Box Observation')
            ->update(['category' => 'C. Right Side/Valve Box Observation']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->where('category', 'Left Side')
            ->update(['category' => 'D. Left Side']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->where('category', 'Top')
            ->update(['category' => 'E. Top']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert T11
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'A. Front Out Side View')
            ->update(['category' => 'Front Out Side View']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'B. Rear Out Side View')
            ->update(['category' => 'Rear Out Side View']);
            
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'C. Right Side')
            ->update(['category' => 'Right Side']);

        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'D. Left Side')
            ->update(['category' => 'Left Side']);
            
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->where('category', 'E. Top')
            ->update(['category' => 'Top']);

        // 2. Revert T50
        // ... Similar logic if needed
    }
};
