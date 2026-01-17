<?php
// debug_cal_query.php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Manually test the suspect query
try {
    echo "Testing MasterIsotankComponent query...\n";
    
    $alerts = \App\Models\MasterIsotankComponent::select('id', 'isotank_id', 'expiry_date', 'component_type', 'position_code', 'serial_number')
             ->where('expiry_date', '<', now()->addMonths(1))
             ->with('isotank:id,iso_number,location')
             ->orderBy('expiry_date', 'asc')
             ->limit(5)
             ->get();
             
    echo "Query Successful. Count: " . $alerts->count() . "\n";
    foreach($alerts as $a) {
        echo "ID: " . $a->id . " - Expiry: " . $a->expiry_date . "\n";
    }
} catch (\Exception $e) {
    echo "Query FAILED: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone.";
