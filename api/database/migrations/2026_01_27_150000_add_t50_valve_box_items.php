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
        $items = [
            [
                'code' => 'T50_C_08',
                'category' => 'Right Side/Valve Box Observation',
                'label' => 'RIGHT: Valve Box',
                'order' => 516
            ],
            [
                'code' => 'T50_D_08',
                'category' => 'Left Side',
                'label' => 'LEFT: Valve Box',
                'order' => 524
            ],
        ];

        $now = now();
        foreach ($items as $item) {
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
            ->whereIn('code', ['T50_C_08', 'T50_D_08'])
            ->delete();
    }
};
