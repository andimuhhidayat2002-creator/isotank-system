<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\InspectionItem;

return new class extends Migration
{
    public function up(): void
    {
        // Define items to add or update for T75 legacy support
        $items = [
            // --- C. VALVES & PIPING EXT ---
            [
                'code' => 'pressure_regulator_esdv',
                'label' => 'Pressure Regulator ESDV',
                'category' => 'C', 
                'input_type' => 'condition',
                'order' => 18,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],

            // --- D. IBOX SYSTEM ---
            [
                'code' => 'ibox_condition',
                'label' => 'IBOX Condition',
                'category' => 'D',
                'input_type' => 'condition',
                'order' => 20,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],

            // --- E. INSTRUMENTS ---
            [
                'code' => 'pressure_gauge_condition',
                'label' => 'Pressure Gauge Condition',
                'category' => 'E',
                'input_type' => 'condition',
                'order' => 30,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],
            [
                'code' => 'level_gauge_condition',
                'label' => 'Level Gauge Condition',
                'category' => 'E',
                'input_type' => 'condition',
                'order' => 31,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],

            // --- F. VACUUM SYSTEM ---
            [
                'code' => 'vacuum_gauge_condition',
                'label' => 'Vacuum Gauge Condition',
                'category' => 'F',
                'input_type' => 'condition',
                'order' => 40,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],
            [
                'code' => 'port_suction_condition',
                'label' => 'Port Suction Condition',
                'category' => 'F',
                'input_type' => 'condition',
                'order' => 41,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],

             // --- G. PSV ---
            [
                'code' => 'psv1_condition',
                'label' => 'PSV1 Condition',
                'category' => 'G',
                'input_type' => 'condition',
                'order' => 50,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],
            [
                'code' => 'psv2_condition',
                'label' => 'PSV2 Condition',
                'category' => 'G',
                'input_type' => 'condition',
                'order' => 51,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],
            [
                'code' => 'psv3_condition',
                'label' => 'PSV3 Condition',
                'category' => 'G',
                'input_type' => 'condition',
                'order' => 52,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],
            [
                'code' => 'psv4_condition',
                'label' => 'PSV4 Condition',
                'category' => 'G',
                'input_type' => 'condition',
                'order' => 53,
                'is_required' => true,
                'applicable_categories' => ['T75']
            ],

        ];
        
        // Also update existing legacy items to ensure they have T75
        $existingCodes = [
            'surface', 'frame', 'tank_plate', 'grounding_system', 'document_container', 'safety_label',
            'venting_pipe', 'explosion_proof_cover', 'valve_box_door', 'valve_box_door_handle',
            'gps_antenna', 'valve_condition', 'valve_position', 'pipe_joint', 'air_source_connection',
            'esdv', 'blind_flange', 'prv'
        ];
        
        InspectionItem::whereIn('code', $existingCodes)->each(function($item) {
             $cats = $item->applicable_categories ?? [];
             if (!in_array('T75', $cats)) {
                 $cats[] = 'T75';
                 $item->applicable_categories = $cats;
                 $item->save();
             }
        });

        foreach ($items as $item) {
            InspectionItem::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }

    public function down(): void
    {
        // No down needed, non-destructive
    }
};
