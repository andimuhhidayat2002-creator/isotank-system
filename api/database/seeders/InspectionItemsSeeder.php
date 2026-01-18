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
            // --- B. GENERAL CONDITION ---
            [
                'code' => 'surface',
                'label' => 'Surface Condition',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 1,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'frame',
                'label' => 'Frame Structure',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 2,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'tank_plate',
                'label' => 'Tank Plate',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 3,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'grounding_system',
                'label' => 'Grounding System',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 4,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'document_container',
                'label' => 'Document Container',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 5,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'safety_label',
                'label' => 'Safety Label',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 6,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'venting_pipe',
                'label' => 'Venting Pipe',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 7,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'explosion_proof_cover',
                'label' => 'Explosion Proof Cover',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 8,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_box_door',
                'label' => 'Valve Box Door',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 9,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_box_door_handle',
                'label' => 'Valve Box Door Handle',
                'category' => 'b',
                'input_type' => 'condition',
                'order' => 10,
                'is_required' => true,
                'applies_to' => 'both',
            ],

            // --- C. VALVE & PIPING ---
            [
                'code' => 'valve_condition',
                'label' => 'Valve Condition',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 11,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'valve_position',
                'label' => 'Valve Position',
                'category' => 'c',
                'input_type' => 'dropdown',
                'order' => 12,
                'is_required' => true,
                'applies_to' => 'both',
                'options' => json_encode(['correct', 'incorrect']),
            ],
            [
                'code' => 'pipe_joint',
                'label' => 'Pipe Joint',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 13,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'air_source_connection',
                'label' => 'Air Source Connection',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 14,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'esdv',
                'label' => 'ESDV (Emergency Shut Down Valve)',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 15,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'blind_flange',
                'label' => 'Blind Flange',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 16,
                'is_required' => true,
                'applies_to' => 'both',
            ],
            [
                'code' => 'prv',
                'label' => 'PRV (Pressure Relief Valve)',
                'category' => 'c',
                'input_type' => 'condition',
                'order' => 17,
                'is_required' => true,
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
