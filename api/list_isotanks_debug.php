<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Existing Isotanks:\n";
foreach(App\Models\MasterIsotank::take(10)->get() as $t) {
    echo "- [" . $t->iso_number . "]\n";
}
