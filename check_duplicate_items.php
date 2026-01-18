#!/usr/bin/env php
<?php

use App\Models\InspectionItem;

require __DIR__ . '/api/vendor/autoload.php';
$app = require __DIR__ . '/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- CHECKING FOR DUPLICATE INSPECTION ITEMS ---\n\n";

// Get all active items grouped by code
$items = InspectionItem::where('is_active', true)
    ->orderBy('code')
    ->get();

$grouped = [];
foreach ($items as $item) {
    if (!isset($grouped[$item->code])) {
        $grouped[$item->code] = [];
    }
    $grouped[$item->code][] = $item;
}

$duplicates = 0;
foreach ($grouped as $code => $itemList) {
    if (count($itemList) > 1) {
        echo "⚠️  DUPLICATE: '$code' appears " . count($itemList) . " times:\n";
        foreach ($itemList as $item) {
            echo "   - ID: {$item->id}, Label: {$item->label}, Category: {$item->category}\n";
        }
        echo "\n";
        $duplicates++;
    }
}

if ($duplicates === 0) {
    echo "✅ No duplicates found! All inspection items have unique codes.\n";
} else {
    echo "Total duplicate codes: $duplicates\n";
}

echo "\n--- SUMMARY ---\n";
echo "Total active items: " . $items->count() . "\n";
echo "Unique codes: " . count($grouped) . "\n";
