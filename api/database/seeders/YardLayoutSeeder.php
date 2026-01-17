<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\YardSlot;

class YardLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define specific layout structure matching the user's "Map" image
        // 1. FILLING_AREA
        //    - BUFFER_TOP (Pagi): 1 Row, 20 Slots (Tiers?) - Let's do 20 Rows of 1 Tier for horizontal spread
        //    - BUFFER_LEFT (Genap): 4 Rows of 10 Tiers? Or 4 Cols? Let's do 4 Rows, 10 Tiers.
        //    - BUFFER_RIGHT (Ganjil): 4 Rows, 10 Tiers.
        //    - CORE:
        //      - WASH_LEFT (6 slots)
        //      - WASH_RIGHT (6 slots)
        //      - FILLING (6 Skids)
        //      - INSP (8 slots)
        //      - LO_LPG (8 slots)
        
        $slots = [];

        // --- FILLING AREA ---
        
        // BUFFER_TOP (Long horizontal strip)
        for ($i=1; $i<=16; $i++) {
            $slots[] = ['FILLING_AREA', 'BUFFER_TOP', $i, 1, "BUF-TOP-$i", true];
            $slots[] = ['FILLING_AREA', 'BUFFER_TOP', $i, 2, "BUF-TOP-$i-T2", true];
        }

        // BUFFER_LEFT (Genap - Vertical Grid left of Filling)
        // 4 Rows, 5 Tiers deep? Or 5 Rows? Image shows a grid.
        for ($r=1; $r<=8; $r++) {
             for ($t=1; $t<=4; $t++) {
                 $slots[] = ['FILLING_AREA', 'BUFFER_LEFT', $r, $t, "BUF-L-$r-T$t", true];
             }
        }

        // BUFFER_RIGHT (Ganjil - Vertical Grid right of Filling)
        for ($r=1; $r<=8; $r++) {
             for ($t=1; $t<=4; $t++) {
                 $slots[] = ['FILLING_AREA', 'BUFFER_RIGHT', $r, $t, "BUF-R-$r-T$t", true];
             }
        }

        // CORE BLOCKS - Refined for "Block" based view
        // WASH_LEFT
        for($i=1; $i<=6; $i++) $slots[] = ['FILLING_AREA', 'CORE', 1, $i, "WASH-L-$i", true, 'WASH_L']; // Use notes or slot_type?
        
        // Let's redefine CORE structure.
        // We want them to position in grid. 
        // Let's make separate Blocks for them: WASH_L, FILL, WASH_R, INSP, LOLPG
        // And position them via CSS relative to Area if needed, or just let them flow.
        
        // Actually, let's stick to the generated content plan but be careful with data types.
        
        $refinedSlots = [];
        
        // BUFFER_TOP
        for ($i=1; $i<=16; $i++) {
            $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BUFFER_TOP', 'row_no'=>1, 'tier_no'=>$i, 'slot_code'=>"BUF-TOP-$i", 'is_active'=>true];
             $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BUFFER_TOP', 'row_no'=>2, 'tier_no'=>$i, 'slot_code'=>"BUF-TOP-$i-l2", 'is_active'=>true];
        }

        // BUFFER_LEFT
        for ($r=1; $r<=8; $r++) {
             for ($t=1; $t<=4; $t++) {
                 $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BUFFER_LEFT', 'row_no'=>$r, 'tier_no'=>$t, 'slot_code'=>"BUF-L-$r-$t", 'is_active'=>true];
             }
        }

        // BUFFER_RIGHT
        for ($r=1; $r<=8; $r++) {
             for ($t=1; $t<=4; $t++) {
                 $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BUFFER_RIGHT', 'row_no'=>$r, 'tier_no'=>$t, 'slot_code'=>"BUF-R-$r-$t", 'is_active'=>true];
             }
        }

        // CORE (Center)
        // We will split CORE into Rows for visual stacking:
        // Row 1: WASH_L, FILL, WASH_R
        // Row 2: INSP
        // Row 3: LO_LPG
        
        // But "Row" in DB is integer.
        // Block: CORE
        // Row 1 (Top sub-block): 
        //   Let's map WASH/FILL to tiers? Tiers are usually stacked.
        //   Let's use Blocks: CORE_WASH, CORE_FILL, etc.
        
        for($i=1; $i<=6; $i++) $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'CORE', 'row_no'=>1, 'tier_no'=>$i, 'slot_code'=>"WASH-L-$i", 'is_active'=>true]; // Wash L
        for($i=1; $i<=6; $i++) $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'CORE', 'row_no'=>2, 'tier_no'=>$i, 'slot_code'=>"FILL-$i", 'is_active'=>true]; // Fill
        for($i=1; $i<=6; $i++) $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'CORE', 'row_no'=>3, 'tier_no'=>$i, 'slot_code'=>"WASH-R-$i", 'is_active'=>true]; // Wash R
        
        // BOTTOM Section
        for($i=1; $i<=8; $i++) $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BOTTOM', 'row_no'=>1, 'tier_no'=>$i, 'slot_code'=>"INSP-$i", 'is_active'=>true]; // Insp
        for($i=1; $i<=8; $i++) $refinedSlots[] = ['yard_area_code'=>'FILLING_AREA', 'block_code'=>'BOTTOM', 'row_no'=>2, 'tier_no'=>$i, 'slot_code'=>"LOLPG-$i", 'is_active'=>true]; // LO

        // ZONES
        foreach(['ZONA_2', 'ZONA_3', 'ZONA_1'] as $zone) {
            for ($r=1; $r<=5; $r++) {
                for ($t=1; $t<=12; $t++) {
                     $refinedSlots[] = ['yard_area_code'=>$zone, 'block_code'=>'A', 'row_no'=>$r, 'tier_no'=>$t, 'slot_code'=>"$zone-R$r-T$t", 'is_active'=>true];
                }
            }
        }

        // JETTY
        for ($r=1; $r<=6; $r++) { 
            for ($t=1; $t<=6; $t++) { 
                $refinedSlots[] = ['yard_area_code'=>'JETTY_AREA', 'block_code'=>'J', 'row_no'=>$r, 'tier_no'=>$t, 'slot_code'=>"JET-R$r-T$t", 'is_active'=>true];
            }
        }

        YardSlot::truncate();
        foreach ($refinedSlots as $slot) {
            YardSlot::create($slot);
        }
    }
}
