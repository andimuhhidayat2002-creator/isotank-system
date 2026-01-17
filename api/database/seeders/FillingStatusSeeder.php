<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FillingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'ready_to_fill',
                'description' => 'Ready to Fill',
                'color' => '#4CAF50', // Green
                'icon' => 'check_circle_outline',
                'order' => 1,
            ],
            [
                'code' => 'filled',
                'description' => 'Filled',
                'color' => '#2196F3', // Blue
                'icon' => 'check_circle',
                'order' => 2,
            ],
            [
                'code' => 'under_maintenance',
                'description' => 'Under Maintenance',
                'color' => '#FF9800', // Orange
                'icon' => 'build_circle',
                'order' => 3,
            ],
            [
                'code' => 'waiting_team_calibration',
                'description' => 'Waiting Team Calibration',
                'color' => '#FFC107', // Amber
                'icon' => 'schedule',
                'order' => 4,
            ],
            [
                'code' => 'class_survey',
                'description' => 'Class Survey',
                'color' => '#9C27B0', // Purple
                'icon' => 'assignment',
                'order' => 5,
            ],
        ];

        // Note: This is for documentation purposes
        // The actual filling_status_code is stored directly in master_isotanks table
        // This seeder can be used if you want to create a reference table later
        
        echo "Filling Status Reference:\n";
        foreach ($statuses as $status) {
            echo "- {$status['code']}: {$status['description']} ({$status['color']})\n";
        }
        echo "\nThese statuses can be used in filling_status_code field.\n";
    }
}
