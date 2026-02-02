<?php
// Debug script for filling status count - Enhanced
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MasterIsotank;
use Illuminate\Support\Facades\DB;

echo "=== FILLING STATUS DEBUG (ENHANCED) ===\n\n";

// Query 1: Total active isotanks
$totalActive = MasterIsotank::where('status', 'active')->count();
echo "Total Active Isotanks: $totalActive\n\n";

// Query 2: Exact same query as dashboard
$fillingRaw = MasterIsotank::where('status', 'active')
    ->select('filling_status_code', DB::raw('count(*) as count'))
    ->groupBy('filling_status_code')
    ->pluck('count', 'filling_status_code');

echo "FillingRaw (pluck result):\n";
echo "Type: " . gettype($fillingRaw) . "\n";
echo "Keys:\n";
foreach ($fillingRaw as $key => $value) {
    $keyType = gettype($key);
    $keyDisplay = $key === '' ? '(empty string)' : ($key === null ? '(null)' : "'$key'");
    echo "  - Key: $keyDisplay (type: $keyType) => Value: $value\n";
}

echo "\nChecking specific keys:\n";
echo "  - fillingRaw[''] = " . ($fillingRaw[''] ?? 'NOT SET') . "\n";
echo "  - fillingRaw[null] = " . ($fillingRaw[null] ?? 'NOT SET') . "\n";

$noStatusCount = ($fillingRaw[''] ?? 0) + ($fillingRaw[null] ?? 0);
echo "\nCalculated noStatusCount: $noStatusCount\n";
