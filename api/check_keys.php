<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = App\Models\MasterIsotank::first();
if ($t) {
    $t->load([
        'itemStatuses',
        'inspectionLogs' => fn($q) => $q->take(1),
    ]);
    echo json_encode($t);
} else {
    echo "No Isotank found";
}
