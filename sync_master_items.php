<?php

use App\Models\MasterIsotank;
use App\Models\InspectionItem;
use App\Models\MasterIsotankItemStatus;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/api/vendor/autoload.php';
$app = require __DIR__ . '/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- SYNCING INSPECTION ITEMS TO MASTER STATUS ---\n";

// 1. Get all active inspection items
$items = InspectionItem::where('is_active', true)->get();
echo "Found " . $items->count() . " active inspection items.\n";

// 2. Get all active isotanks
$isotanks = MasterIsotank::where('status', 'active')->get();
echo "Found " . $isotanks->count() . " active isotanks.\n";

$count = 0;
$created = 0;

foreach ($isotanks as $iso) {
    echo "Processing ISO: " . $iso->iso_number . "... ";
    $localCreated = 0;

    foreach ($items as $item) {
        // Check if status exists
        $exists = MasterIsotankItemStatus::where('isotank_id', $iso->id)
            ->where('item_code', $item->code)
            ->exists();

        if (!$exists) {
            MasterIsotankItemStatus::create([
                'isotank_id' => $iso->id,
                'item_code' => $item->code,
                'item_name' => $item->code, // Legacy support
                'condition' => 'unknown',   // Default
                'description' => $item->label,
            ]);
            $localCreated++;
            $created++;
        }
    }
    echo "Added $localCreated new items.\n";
    $count++;
}

echo "\n------------------------------------------------\n";
echo "Sync Completed.\n";
echo "Total Isotanks Processed: $count\n";
echo "Total New Status Records Created: $created\n";
