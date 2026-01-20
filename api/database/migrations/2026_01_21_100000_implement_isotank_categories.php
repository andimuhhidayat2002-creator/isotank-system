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
        // 1. Add 'tank_category' to master_isotanks
        Schema::table('master_isotanks', function (Blueprint $table) {
            // Defaulting to T75 for all existing tanks
            $table->string('tank_category')->default('T75')->after('part_number')->comment('T75, T11, T50, etc.');
        });

        // 2. Add 'applicable_categories' to inspection_items
        Schema::table('inspection_items', function (Blueprint $table) {
            // Store as JSON array, e.g., ["T75", "T11"]
            $table->json('applicable_categories')->nullable()->after('is_active');
        });

        // 3. PATENT EXISTING DATA: Set all current items as T75 ONLY
        // This ensures the current form remains exactly as is (T75 exclusive)
        // until you explicitly add T11/T50 tags to shared items.
        DB::table('inspection_items')->update([
            'applicable_categories' => json_encode(['T75'])
        ]);
        
        // 4. Update existing Isotanks to be T75 by default
        DB::table('master_isotanks')->update([
            'tank_category' => 'T75'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_isotanks', function (Blueprint $table) {
            $table->dropColumn('tank_category');
        });

        Schema::table('inspection_items', function (Blueprint $table) {
            $table->dropColumn('applicable_categories');
        });
    }
};
