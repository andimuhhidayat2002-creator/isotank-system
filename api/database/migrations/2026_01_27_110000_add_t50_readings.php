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
        // Define the reading items to add
        // Right Side
        $items = [
            [
                'code' => 'T50_C_05_VAL', 
                'category' => 'Right Side/Valve Box Observation', 
                'label' => 'RIGHT: Thermometer Reading (°C)',
                'input_type' => 'number',
                'order' => 513 // C_05 is order 512
            ],
            [
                'code' => 'T50_C_06_VAL', 
                'category' => 'Right Side/Valve Box Observation', 
                'label' => 'RIGHT: Pressure Gauge Reading (MPa)',
                'input_type' => 'number',
                'order' => 515 // C_06 is order 514
            ],
            // Left Side
            [
                'code' => 'T50_D_05_VAL', 
                'category' => 'Left Side', 
                'label' => 'LEFT: Thermometer Reading (°C)',
                'input_type' => 'number',
                'order' => 521 // D_05 is order 520
            ],
            [
                'code' => 'T50_D_06_VAL', 
                'category' => 'Left Side', 
                'label' => 'LEFT: Pressure Gauge Reading (MPa)',
                'input_type' => 'number',
                'order' => 523 // D_06 is order 522
            ],
        ];

        $now = now();
        foreach ($items as $item) {
            DB::table('inspection_items')->updateOrInsert(
                ['code' => $item['code']],
                [
                    'label' => $item['label'],
                    'category' => $item['category'],
                    'input_type' => $item['input_type'],
                    'is_required' => true,
                    'is_active' => true,
                    'applies_to' => 'both',
                    'applicable_categories' => json_encode(['T50']),
                    'order' => $item['order'],
                    'updated_at' => $now,
                    'created_at' => $now
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inspection_items')
            ->whereIn('code', ['T50_C_05_VAL', 'T50_C_06_VAL', 'T50_D_05_VAL', 'T50_D_06_VAL'])
            ->delete();
    }
};
