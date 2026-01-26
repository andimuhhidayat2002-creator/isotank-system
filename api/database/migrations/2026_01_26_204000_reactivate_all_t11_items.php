<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reactivate ALL T11 items so the inspector can see the full list
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->update(['is_active' => true]);
            
        // Note: The labels with "FRONT:", "REAR:" prefixes will remain for the 14 items,
        // which is good for clarity.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to deactivate
    }
};
