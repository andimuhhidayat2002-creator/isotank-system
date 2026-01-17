<?php
// fix_vacuum.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\MasterIsotank::where('iso_number', 'HAIU240001-8')->first();
if ($t && $t->measurementStatus) {
    $t->measurementStatus->update(['vacuum_mtorr' => 1]);
    echo "Reset Success: Vacuum set to 1 mTorr for " . $t->iso_number . "\n";
} else {
    echo "Isotank or Measurement Status not found.\n";
}
