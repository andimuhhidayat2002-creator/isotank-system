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
        // Define the T50 specific items based on the user request image
        $items = [
            // A. Front Out Side View
            ['category' => 'Front Out Side View', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T50_A_01'],
            ['category' => 'Front Out Side View', 'label' => 'Frame Condition', 'code' => 'T50_A_02'],

            // B. Rear Out Side View
            ['category' => 'Rear Out Side View', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T50_B_01'],
            ['category' => 'Rear Out Side View', 'label' => 'Frame Condition', 'code' => 'T50_B_02'],
            ['category' => 'Rear Out Side View', 'label' => 'Name Plate', 'code' => 'T50_B_03'],
            ['category' => 'Rear Out Side View', 'label' => 'Ladder', 'code' => 'T50_B_04'],
            ['category' => 'Rear Out Side View', 'label' => 'Level Gauge', 'code' => 'T50_B_05'],
            ['category' => 'Rear Out Side View', 'label' => 'Grounding', 'code' => 'T50_B_06'],

            // C. Right Side/Valve Box Observation
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T50_C_01'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Frame Condition', 'code' => 'T50_C_02'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Valve Liquid Phase', 'code' => 'T50_C_03'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Gas Phase', 'code' => 'T50_C_04'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Thermometer', 'code' => 'T50_C_05'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Pressure Gauge', 'code' => 'T50_C_06'],
            ['category' => 'Right Side/Valve Box Observation', 'label' => 'Release Valve', 'code' => 'T50_C_07'],

            // D. Left Side
            ['category' => 'Left Side', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T50_D_01'],
            ['category' => 'Left Side', 'label' => 'Frame Condition', 'code' => 'T50_D_02'],
            ['category' => 'Left Side', 'label' => 'Valve Liquid Phase', 'code' => 'T50_D_03'],
            ['category' => 'Left Side', 'label' => 'Gas Phase', 'code' => 'T50_D_04'],
            ['category' => 'Left Side', 'label' => 'Thermometer', 'code' => 'T50_D_05'],
            ['category' => 'Left Side', 'label' => 'Pressure Gauge', 'code' => 'T50_D_06'],
            ['category' => 'Left Side', 'label' => 'Release Valve', 'code' => 'T50_D_07'],

            // E. Top
            ['category' => 'Top', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T50_E_01'],
            ['category' => 'Top', 'label' => 'Frame Condition', 'code' => 'T50_E_02'],
            ['category' => 'Top', 'label' => 'Safety Valve 1', 'code' => 'T50_E_03'],
            ['category' => 'Top', 'label' => 'Safety Valve 2', 'code' => 'T50_E_04'],
            ['category' => 'Top', 'label' => 'Walkway', 'code' => 'T50_E_05'],
        ];

        $now = now();
        $order = 1;

        foreach ($items as $item) {
            DB::table('inspection_items')->insert([
                'code' => $item['code'],
                'label' => $item['label'],
                'category' => $item['category'], // This will be used for grouping in Flutter
                'input_type' => 'condition', 
                'is_required' => true,
                'is_active' => true,
                'applies_to' => 'both',
                'applicable_categories' => json_encode(['T50']), // Explicitly for T50 only
                'options' => null,
                'order' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T50')
            ->delete();
    }
};
