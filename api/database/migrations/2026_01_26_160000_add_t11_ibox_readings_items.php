<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add IBOX readings items for T11 in Section C (Right Side)
        DB::table('inspection_items')->insert([
            [
                'code' => 'T11_C_05',
                'label' => 'IBOX Temperature #1 (°C)',
                'category' => 'Right Side',
                'input_type' => 'number',
                'applicable_categories' => '["T11"]',
                'is_active' => 1,
                'order' => 305,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'T11_C_06',
                'label' => 'IBOX Temperature #2 (°C)',
                'category' => 'Right Side',
                'input_type' => 'number',
                'applicable_categories' => '["T11"]',
                'is_active' => 1,
                'order' => 306,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'T11_C_07',
                'label' => 'IBOX Pressure (Bar)',
                'category' => 'Right Side',
                'input_type' => 'number',
                'applicable_categories' => '["T11"]',
                'is_active' => 1,
                'order' => 307,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'T11_C_08',
                'label' => 'IBOX Level (%)',
                'category' => 'Right Side',
                'input_type' => 'number',
                'applicable_categories' => '["T11"]',
                'is_active' => 1,
                'order' => 308,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        DB::table('inspection_items')->whereIn('code', [
            'T11_C_05', 'T11_C_06', 'T11_C_07', 'T11_C_08'
        ])->delete();
    }
};
