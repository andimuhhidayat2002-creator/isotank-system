<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\InspectionItem;

return new class extends Migration
{
    public function up(): void
    {
        // Fix inconsistencies in Category Cases produced by previous seeding
        // We match the exact category key used by legacy items (confirmed as 'b', 'c', etc. from previous seeders)

        // 1. Fix Pressure Regulator ESDV -> 'c' (C. VALVE & PIPE SYSTEM)
        InspectionItem::where('code', 'pressure_regulator_esdv')->update(['category' => 'c']);

        // 2. Fix IBOX -> 'd' (D. IBOX SYSTEM)
        InspectionItem::where('code', 'ibox_condition')->update(['category' => 'd']);

        // 3. Fix Instruments -> 'e' (E. INSTRUMENT)
        InspectionItem::whereIn('code', ['pressure_gauge_condition', 'level_gauge_condition'])->update(['category' => 'e']);

        // 4. Fix Vacuum -> 'f' (F. VACUUM)
        InspectionItem::whereIn('code', ['vacuum_gauge_condition', 'port_suction_condition'])->update(['category' => 'f']);

        // 5. Fix PSV -> 'g' (G. PSV)
        InspectionItem::whereIn('code', [
            'psv1_condition', 'psv2_condition', 'psv3_condition', 'psv4_condition'
        ])->update(['category' => 'g']);

        // Ensure legacy items have T75
        $legacy = [
             'surface', 'frame', 'tank_plate', 'grounding_system', 'document_container', 'safety_label',
            'venting_pipe', 'explosion_proof_cover', 'valve_box_door', 'valve_box_door_handle',
            'gps_antenna', 'valve_condition', 'valve_position', 'pipe_joint', 'air_source_connection',
            'esdv', 'blind_flange', 'prv'
        ];
        
        $items = InspectionItem::whereIn('code', $legacy)->get();
        foreach($items as $i) {
            $cats = $i->applicable_categories ?? [];
            if (!in_array('T75', $cats)) {
                $cats[] = 'T75';
                $i->applicable_categories = $cats;
                $i->save();
            }
        }
    }

    public function down(): void
    {
    }
};
