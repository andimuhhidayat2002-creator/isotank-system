<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$iso = 'HAIU240001-8'; // From the user's screenshot
$t = App\Models\MasterIsotank::where('iso_number', $iso)->first();

if ($t) {
    echo "Isotank ID: " . $t->id . "\n";
    echo "Logs count: " . $t->inspectionLogs()->count() . "\n";
    echo "History sample:\n";
    print_r($t->inspectionLogs()->latest()->take(2)->get()->toArray());
    echo "\nItem Statuses count: " . $t->itemStatuses()->count() . "\n";
    echo "JSON keys sample:\n";
    echo json_encode($t->load(['inspectionLogs' => fn($q) => $q->take(1)]));
} else {
    echo "Isotank $iso not found";
}
