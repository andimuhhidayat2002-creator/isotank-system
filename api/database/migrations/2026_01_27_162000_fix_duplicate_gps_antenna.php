<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Deactivate the duplicate/legacy item with weird code
        DB::table('inspection_items')
            ->where('code', 'GPS/_4G/_LPLAN_Antenna')
            ->update(['is_active' => false]);

        // Ensure the correct item is active and has correct label
        DB::table('inspection_items')
            ->where('code', 'gps_antenna')
            ->update([
                'is_active' => true,
                'label' => 'GPS/4G/LP LAN Antenna', 
                'category' => 'b',
                'applicable_categories' => json_encode(['T75']) // Ensure string JSON for consistency
            ]);
    }

    public function down()
    {
        // No down needed really, this is a fix
    }
};
