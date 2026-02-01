<?php
require __DIR__.'/api/vendor/autoload.php';
$app = require_once __DIR__.'/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterIsotank;

echo "--- CHECKING ISO NUMBER FORMATS ---\n";
$tanks = MasterIsotank::take(10)->get();

foreach ($tanks as $tank) {
    echo "ID: {$tank->id} | ISO: '{$tank->iso_number}'\n";
}
echo "-----------------------------------\n";
