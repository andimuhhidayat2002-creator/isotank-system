<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$iso = 'KYNU241045-2';
$tank = App\Models\MasterIsotank::where('iso_number', 'like', '%KYNU241045-2%')->first();

if (!$tank) {
    echo "Isotank $iso not found.\n";
    exit;
}

echo "Isotank ID: " . $tank->id . "\n";
$comps = App\Models\MasterIsotankComponent::where('isotank_id', $tank->id)->get();

foreach ($comps as $c) {
    echo "ID: {$c->id} | Type: {$c->component_type} | Pos: '{$c->position_code}' | SN: {$c->serial_number} | Exp: {$c->expiry_date}\n";
}
