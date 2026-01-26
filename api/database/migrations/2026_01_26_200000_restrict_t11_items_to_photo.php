<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Deactivate ALL T11 items first to "clean slate" (or we can do it selectively)
        // We only want the 14 items from the user photo
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->update(['is_active' => false]);

        // 2. Define the exact 14 items from photo and update their labels + reactivate
        $photoItems = [
            'T11_A_01' => 'FRONT: Tank Surface & Paint Condition',
            'T11_A_02' => 'FRONT: Frame Condition',
            
            'T11_B_01' => 'REAR: Tank Surface & Paint Condition',
            'T11_B_02' => 'REAR: Frame Condition',
            'T11_B_03' => 'REAR: Data Plate',
            'T11_B_09' => 'REAR: Ladder',
            
            'T11_C_01' => 'RIGHT: Tank Surface & Paint Condition',
            'T11_C_02' => 'RIGHT: Frame Condition',
            
            'T11_D_01' => 'LEFT: Tank Surface & Paint Condition',
            'T11_D_02' => 'LEFT: Frame Condition',
            'T11_D_04' => 'LEFT: Document Holder',
            
            'T11_E_01' => 'TOP: Tank Surface & Paint Condition',
            'T11_E_02' => 'TOP: Frame Condition',
            'T11_E_09' => 'TOP: Antena,GPS,4G',
        ];

        foreach ($photoItems as $code => $newLabel) {
            DB::table('inspection_items')
                ->where('code', $code)
                ->update([
                    'label' => $newLabel,
                    'is_active' => true,
                    'is_required' => true
                ]);
        }

        // 3. Keep other categories (T75, T50) untouched
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback labels is hard without storing old ones, 
        // but we can just reactivate all T11 items.
        DB::table('inspection_items')
            ->whereJsonContains('applicable_categories', 'T11')
            ->update(['is_active' => true]);
    }
};
