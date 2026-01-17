<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Grab first tank that resembles the one in screenshot, or just list a few components to see structure
echo "Checking MasterIsotankComponent structure...\n";

$tank = App\Models\MasterIsotank::where('iso_number', 'like', '%KYNU210089-0%')->first();

if (!$tank) {
    echo "Isotank KYNU241045-2 not found.\n";
    exit;
}

echo "Isotank ID: {$tank->id}\n";

$legacy = App\Models\MasterIsotankCalibrationStatus::where('isotank_id', $tank->id)->get();

if ($legacy->isEmpty()) {
    echo "No LEGACY calibration data found for ID {$tank->id}.\n";
}

foreach ($legacy as $l) {
    echo "LEGACY Item: {$l->item_name} | SN: {$l->serial_number} | Date: " . ($l->calibration_date ? $l->calibration_date->format('Y-m-d') : 'null') . "\n";
}

$comps = App\Models\MasterIsotankComponent::where('isotank_id', $tank->id)->get();
if ($comps->isEmpty()) {
    echo "No NEW COMPONENTS found for ID {$tank->id}.\n";
}
foreach ($comps as $c) {
    echo "NEW Item: {$c->component_type} | SN: {$c->serial_number} | Pos: {$c->position_code}\n";
}
