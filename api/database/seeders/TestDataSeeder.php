<?php

namespace Database\Seeders;

use App\Models\MasterIsotank;
use App\Models\InspectionJob;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $isotank = MasterIsotank::firstOrCreate(
            ['iso_number' => 'KYNU1234567'],
            [
                'location' => 'SMGRS',
                'status' => 'active',
                'owner' => 'TEST OWNER',
                'product' => 'CHEMICAL A'
            ]
        );

        InspectionJob::firstOrCreate(
            [
                'isotank_id' => $isotank->id,
                'activity_type' => 'incoming_inspection',
                'status' => 'open'
            ],
            [
                'planned_date' => now(),
            ]
        );
    }
}
