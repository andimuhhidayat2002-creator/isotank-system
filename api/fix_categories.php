<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionItem;

$map = [
    'Front Out Side View' => 'a',
    'Rear Out Side View' => 'b',
    'Right Side' => 'c',
    'Right Side/Valve Box Observation' => 'c',
    'Left Side' => 'd',
    'Top' => 'e'
];

$count = 0;
foreach (InspectionItem::all() as $item) {
    if (isset($map[$item->category])) {
        $old = $item->category;
        $item->category = $map[$item->category];
        $item->save();
        echo "Updated [{$item->id}] {$item->label}: {$old} -> {$item->category}\n";
        $count++;
    }
}

echo "Total updated: {$count}\n";
