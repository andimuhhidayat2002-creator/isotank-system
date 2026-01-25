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
        // Define the T11 specific items based on the user request
        $items = [
            // A. Front Out Side View
            ['category' => 'Front Out Side View', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T11_A_01'],
            ['category' => 'Front Out Side View', 'label' => 'Frame Condition', 'code' => 'T11_A_02'],

            // B. Rear Out Side View
            ['category' => 'Rear Out Side View', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T11_B_01'],
            ['category' => 'Rear Out Side View', 'label' => 'Frame Condition', 'code' => 'T11_B_02'],
            ['category' => 'Rear Out Side View', 'label' => 'Data Plate', 'code' => 'T11_B_03'],
            ['category' => 'Rear Out Side View', 'label' => 'Thermometer', 'code' => 'T11_B_04'],
            ['category' => 'Rear Out Side View', 'label' => 'Earth Connection', 'code' => 'T11_B_05'],
            ['category' => 'Rear Out Side View', 'label' => 'Foot Valve', 'code' => 'T11_B_06'],
            ['category' => 'Rear Out Side View', 'label' => 'Steam Inlet Pipe', 'code' => 'T11_B_07'],
            ['category' => 'Rear Out Side View', 'label' => 'Steam Outlet Pipe', 'code' => 'T11_B_08'],
            ['category' => 'Rear Out Side View', 'label' => 'Ladder', 'code' => 'T11_B_09'],

            // C. Right Side
            ['category' => 'Right Side', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T11_C_01'],
            ['category' => 'Right Side', 'label' => 'Frame Condition', 'code' => 'T11_C_02'],
            ['category' => 'Right Side', 'label' => 'I-Box Condition', 'code' => 'T11_C_03'],
            ['category' => 'Right Side', 'label' => 'I-Box Battery (≥ 40%)', 'code' => 'T11_C_04'],

            // D. Left Side
            ['category' => 'Left Side', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T11_D_01'],
            ['category' => 'Left Side', 'label' => 'Frame Condition', 'code' => 'T11_D_02'],
            ['category' => 'Left Side', 'label' => 'Remote Closure', 'code' => 'T11_D_03'],
            ['category' => 'Left Side', 'label' => 'Document Holder', 'code' => 'T11_D_04'],

            // E. Top
            ['category' => 'Top', 'label' => 'Tank Surface & Paint Condition', 'code' => 'T11_E_01'],
            ['category' => 'Top', 'label' => 'Frame Condition', 'code' => 'T11_E_02'],
            ['category' => 'Top', 'label' => 'Air Inlet Assembly', 'code' => 'T11_E_03'],
            ['category' => 'Top', 'label' => 'Top discharge Provision', 'code' => 'T11_E_04'],
            ['category' => 'Top', 'label' => 'Walkway Assembly', 'code' => 'T11_E_05'],
            ['category' => 'Top', 'label' => 'Manlid Assembly', 'code' => 'T11_E_06'],
            ['category' => 'Top', 'label' => 'Relief Valve Assembly', 'code' => 'T11_E_07'], 
            ['category' => 'Top', 'label' => 'Relief Valve Provision', 'code' => 'T11_E_08'],
            ['category' => 'Top', 'label' => 'Antena,GPS,4G', 'code' => 'T11_E_09'],
        ];

        $now = now();
        $order = 1;

        foreach ($items as $item) {
            DB::table('inspection_items')->insert([
                'code' => $item['code'],
                'label' => $item['label'],
                'category' => $item['category'],
                'input_type' => 'condition', 
                'is_required' => true,
                'is_active' => true,
                'applies_to' => 'both',
                'applicable_categories' => json_encode(['T11']), // Explicitly for T11 only
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
        // Delete items where applicable_categories contains T11
        // Note: This is a simplistic rollback. If we update items to share categories, be careful.
        // For now, these are T11 specific, so it's safe.
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->delete();
    }
};
