<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterIsotank;
use App\Models\InspectionItem;
use App\Models\MasterIsotankItemStatus;

echo "--- DEBUG & SYNC ---\n";

// 1. Check Isotank Statuses
$allIsotanks = MasterIsotank::all();
echo "Total Isotanks in DB: " . $allIsotanks->count() . "\n";
$statuses = $allIsotanks->pluck('status')->unique();
echo "Found Statuses: " . $statuses->implode(', ') . "\n";

// 2. Get Items
$items = InspectionItem::where('is_active', true)->get();
echo "Active Items: " . $items->count() . "\n";

// 3. FORCE SYNC FOR ALL ISOTANKS (Not just 'active')
echo "Syncing all isotanks...\n";
$totalCreated = 0;

foreach ($allIsotanks as $iso) {
    echo "ISO: " . $iso->iso_number . " (Status: " . ($iso->status ?? 'NULL') . ")\n";
    
    foreach ($items as $item) {
        $exists = MasterIsotankItemStatus::where('isotank_id', $iso->id)
            ->where('item_name', $item->code) // Changed from item_code to item_name based on SQL error
            ->exists();

        if (!$exists) {
            MasterIsotankItemStatus::create([
                'isotank_id' => $iso->id,
                'item_name' => $item->code, // Changed from item_code
                // 'item_code' => $item->code, // Removed: column doesn't exist yet
                'condition' => 'na', // FIXED: ENUM doesn't accept 'unknown', used 'na' instead
                'description' => $item->label,
            ]);
            $totalCreated++;
            echo "   + Created status for item: {$item->code}\n";
        }
    }
}

echo "Done. Created $totalCreated new records.\n";
