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
        // Add Level Gauge Reading for T50 (Rear Section)
        DB::table('inspection_items')->updateOrInsert(
            ['code' => 'T50_B_05_VAL'],
            [
                'label' => 'REAR: Level Gauge Reading (%)',
                'category' => 'Rear Out Side View',
                'input_type' => 'number',
                'is_required' => true,
                'is_active' => true,
                'applies_to' => 'both',
                'applicable_categories' => json_encode(['T50']),
                'order' => 507, // T50_B_05 (Condition) is 506
                'updated_at' => now(),
                'created_at' => now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inspection_items')->where('code', 'T50_B_05_VAL')->delete();
    }
};
