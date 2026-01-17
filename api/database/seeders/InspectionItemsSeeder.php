<?php

namespace Database\Seeders;

use App\Models\InspectionItem;
use Illuminate\Database\Seeder;

class InspectionItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // --- GENERAL CONDITION (External) ---
            [
                'code' => 'surface',
                'label' => 'Surface Condition',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 1,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'frame',
                'label' => 'Frame Structure',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 2,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'tank_plate',
                'label' => 'Tank Plate',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 3,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'grounding_system',
                'label' => 'Grounding System',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 4,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'document_container',
                'label' => 'Document Container',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 5,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'safety_label',
                'label' => 'Safety Label',
                'category' => 'external',
                'input_type' => 'condition',
                'order' => 6,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- SAFETY EQUIPMENT ---
            [
                'code' => 'venting_pipe',
                'label' => 'Venting Pipe',
                'category' => 'safety',
                'input_type' => 'condition',
                'order' => 7,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'explosion_proof_cover',
                'label' => 'Explosion Proof Cover',
                'category' => 'safety',
                'input_type' => 'condition',
                'order' => 8,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_box_door',
                'label' => 'Valve Box Door',
                'category' => 'safety',
                'input_type' => 'condition',
                'order' => 9,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_box_door_handle',
                'label' => 'Valve Box Door Handle',
                'category' => 'safety',
                'input_type' => 'condition',
                'order' => 10,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- VALVE & PIPING ---
            [
                'code' => 'valve_condition',
                'label' => 'Valve Condition',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 11,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_position',
                'label' => 'Valve Position',
                'category' => 'valve',
                'input_type' => 'condition', // technically correct/incorrect, but condition works
                'order' => 12,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'pipe_joint',
                'label' => 'Pipe Joint',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 13,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'air_source_connection',
                'label' => 'Air Source Connection',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 14,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'esdv',
                'label' => 'ESDV (Emergency Shut Down Valve)',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 15,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'blind_flange',
                'label' => 'Blind Flange',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 16,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'prv',
                'label' => 'PRV (Pressure Relief Valve)',
                'category' => 'valve',
                'input_type' => 'condition',
                'order' => 17,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- IBOX SYSTEM ---
            [
                'code' => 'ibox_condition',
                'label' => 'iBox Condition',
                'category' => 'measurement',
                'input_type' => 'condition',
                'order' => 18,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- INSTRUMENT CONDITION ---
            [
                'code' => 'pressure_gauge_condition',
                'label' => 'Pressure Gauge Condition',
                'category' => 'measurement',
                'input_type' => 'condition',
                'order' => 19,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'level_gauge_condition',
                'label' => 'Level Gauge Condition',
                'category' => 'measurement',
                'input_type' => 'condition',
                'order' => 20,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'vacuum_gauge_condition',
                'label' => 'Vacuum Gauge Condition',
                'category' => 'measurement',
                'input_type' => 'condition',
                'order' => 21,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'vacuum_port_suction_condition',
                'label' => 'Vacuum Port Suction Condition',
                'category' => 'measurement',
                'input_type' => 'condition',
                'order' => 22,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- PSV CONDITION (Simplified list for dynamic items) ---
            [
                'code' => 'psv_condition',
                'label' => 'PSV Overall Condition',
                'category' => 'safety',
                'input_type' => 'condition',
                'order' => 23,
                'is_required' => false,
                'applies_to' => 'both',
            ],

            // --- NEW ITEM ---
            [
                'code' => 'cleanliness',
                'label' => 'Internal Cleanliness',
                'category' => 'internal',
                'input_type' => 'condition',
                'description' => 'Check if the tank is clean and free of residue',
                'order' => 24,
                'is_required' => true,
                'is_active' => true,
                'applies_to' => 'both',
            ],
        ];

        foreach ($items as $item) {
            InspectionItem::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }

        $this->command->info('Inspection items seeded successfully!');
    }
}
