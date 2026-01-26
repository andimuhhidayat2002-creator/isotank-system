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
        // 1. Deactivate ALL T50 items first to ensure a clean state
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->update(['is_active' => false]);

        // 2. Define the exact 27 items from the photo with prefixed labels
        $photoItems = [
            // A. Front Out Side View
            ['code' => 'T50_A_01', 'category' => 'Front Out Side View', 'label' => 'FRONT: Tank Surface & Paint Condition'],
            ['code' => 'T50_A_02', 'category' => 'Front Out Side View', 'label' => 'FRONT: Frame Condition'],

            // B. Rear Out Side View
            ['code' => 'T50_B_01', 'category' => 'Rear Out Side View', 'label' => 'REAR: Tank Surface & Paint Condition'],
            ['code' => 'T50_B_02', 'category' => 'Rear Out Side View', 'label' => 'REAR: Frame Condition'],
            ['code' => 'T50_B_03', 'category' => 'Rear Out Side View', 'label' => 'REAR: Name Plate'],
            ['code' => 'T50_B_04', 'category' => 'Rear Out Side View', 'label' => 'REAR: Ladder'],
            ['code' => 'T50_B_05', 'category' => 'Rear Out Side View', 'label' => 'REAR: Level Gauge'],
            ['code' => 'T50_B_06', 'category' => 'Rear Out Side View', 'label' => 'REAR: Grounding'],

            // C. Right Side/Valve Box Observation
            ['code' => 'T50_C_01', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Tank Surface & Paint Condition'],
            ['code' => 'T50_C_02', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Frame Condition'],
            ['code' => 'T50_C_03', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Valve Liquid Phase'],
            ['code' => 'T50_C_04', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Gas Phase'],
            ['code' => 'T50_C_05', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Thermometer'],
            ['code' => 'T50_C_06', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Pressure Gauge'],
            ['code' => 'T50_C_07', 'category' => 'Right Side/Valve Box Observation', 'label' => 'RIGHT: Release Valve'],

            // D. Left Side
            ['code' => 'T50_D_01', 'category' => 'Left Side', 'label' => 'LEFT: Tank Surface & Paint Condition'],
            ['code' => 'T50_D_02', 'category' => 'Left Side', 'label' => 'LEFT: Frame Condition'],
            ['code' => 'T50_D_03', 'category' => 'Left Side', 'label' => 'LEFT: Valve Liquid Phase'],
            ['code' => 'T50_D_04', 'category' => 'Left Side', 'label' => 'LEFT: Gas Phase'],
            ['code' => 'T50_D_05', 'category' => 'Left Side', 'label' => 'LEFT: Thermometer'],
            ['code' => 'T50_D_06', 'category' => 'Left Side', 'label' => 'LEFT: Pressure Gauge'],
            ['code' => 'T50_D_07', 'category' => 'Left Side', 'label' => 'LEFT: Release Valve'],

            // E. Top
            ['code' => 'T50_E_01', 'category' => 'Top', 'label' => 'TOP: Tank Surface & Paint Condition'],
            ['code' => 'T50_E_02', 'category' => 'Top', 'label' => 'TOP: Frame Condition'],
            ['code' => 'T50_E_03', 'category' => 'Top', 'label' => 'TOP: Safety Valve 1'],
            ['code' => 'T50_E_04', 'category' => 'Top', 'label' => 'TOP: Safety Valve 2'],
            ['code' => 'T50_E_05', 'category' => 'Top', 'label' => 'TOP: Walkway'],
        ];

        $now = now();
        foreach ($photoItems as $index => $item) {
            DB::table('inspection_items')->updateOrInsert(
                ['code' => $item['code']],
                [
                    'label' => $item['label'],
                    'category' => $item['category'],
                    'input_type' => 'condition',
                    'is_required' => true,
                    'is_active' => true,
                    'applies_to' => 'both',
                    'applicable_categories' => json_encode(['T50']),
                    'order' => 500 + $index, // Give it a high order to group them
                    'updated_at' => $now
                ]
            );
        }

        // 3. Ensure we didn't touch T11 or T75
        // (The logic above only deactivates T50 items and updates/inserts specifically matching codes)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-activate all T50 items
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->update(['is_active' => true]);
    }
};
