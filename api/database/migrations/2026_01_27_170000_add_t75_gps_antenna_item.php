<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\InspectionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(InspectionItem::class)) {
            // Add GPS/4G/LP LAN Antenna item for T75
            InspectionItem::updateOrCreate(
                ['code' => 'gps_antenna'],
                [
                    'label' => 'GPS/4G/LP LAN Antenna',
                    'category' => 'b', // General Condition / External
                    'input_type' => 'condition',
                    'description' => 'GPS/4G/LP LAN Antenna condition',
                    'order' => 115, // After Handle lock Valve Box Door
                    'is_active' => true,
                    'is_required' => false,
                    'applies_to' => 'both', // both, incoming, outgoing
                    'applicable_categories' => json_encode(['T75']),
                    'options' => json_encode(['good', 'not_good', 'need_attention', 'na']),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (class_exists(InspectionItem::class)) {
            InspectionItem::where('code', 'gps_antenna')->delete();
        }
    }
};
