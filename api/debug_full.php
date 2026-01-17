<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$iso = 'KYNU210089-0';
$tank = App\Models\MasterIsotank::where('iso_number', 'like', "%$iso%")->first();

if (!$tank) {
    echo "TIDAK DITEMUKAN: $iso\n";
    exit;
}

echo "=== TANK ID: {$tank->id} ===\n";

echo "\n--- 1. NEW COMPONENTS (MasterIsotankComponent) ---\n";
$comps = App\Models\MasterIsotankComponent::where('isotank_id', $tank->id)->get();
if ($comps->isEmpty()) echo "(KOSONG)\n";
foreach ($comps as $c) {
    echo "[{$c->component_type}] Pos: {$c->position_code} | SN: '{$c->serial_number}' | Date: " . ($c->last_calibration_date ? $c->last_calibration_date->format('Y-m-d') : 'NULL') . "\n";
}

echo "\n--- 2. LEGACY (MasterIsotankCalibrationStatus) ---\n";
$legacy = App\Models\MasterIsotankCalibrationStatus::where('isotank_id', $tank->id)->get();
if ($legacy->isEmpty()) echo "(KOSONG)\n";
foreach ($legacy as $l) {
    echo "[{$l->item_name}] SN: '{$l->serial_number}' | Date: " . ($l->calibration_date ? $l->calibration_date->format('Y-m-d') : 'NULL') . "\n";
}
