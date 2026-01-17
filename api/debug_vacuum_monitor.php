<?php
// debug_vacuum_monitor.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\VacuumLog;
use App\Models\MasterIsotank;
use App\Models\MasterIsotankMeasurementStatus;
use App\Models\VacuumSuctionActivity;

echo "Starting Debug...\n";

try {
    // 1. Exceed Frequency
    echo "1. Checking Exceed Frequency...\n";
    $exceedFrequency = VacuumLog::where('vacuum_reading', '>', 8)
        ->select('isotank_id', DB::raw('count(*) as count'))
        ->with('isotank:id,iso_number,location')
        ->groupBy('isotank_id')
        ->limit(20)
        ->get();
    echo "   Success. Count: " . $exceedFrequency->count() . "\n";

    // 2. Trend Data
    echo "2. Checking Trend Data Query...\n";
    try {
        $trendData = VacuumLog::select(
            DB::raw("DATE_FORMAT(check_datetime, '%Y-%m') as month"), 
            DB::raw('AVG(vacuum_value_mtorr) as avg_vacuum')
        )
        ->where('check_datetime', '>=', now()->subYear())
        ->groupBy(DB::raw("DATE_FORMAT(check_datetime, '%Y-%m')"))
        ->orderBy(DB::raw("DATE_FORMAT(check_datetime, '%Y-%m')"))
        ->get();
        echo "   Success. Count: " . $trendData->count() . "\n";
    } catch (\Exception $e) {
        echo "   FAILED Trend Data: " . $e->getMessage() . "\n";
    }

    // 3. Comparison Logic
    echo "3. Checking Comparison Logic...\n";
    $activeTanks = MasterIsotank::with('measurementStatus')->where('status', 'active')->limit(50)->get();
    echo "   Active Tanks Found: " . $activeTanks->count() . "\n";

    foreach ($activeTanks as $tank) {
        $latest = VacuumLog::where('isotank_id', $tank->id)->orderByDesc('check_datetime')->first();
        if ($latest) {
             // Simulate calculation
             $val = $latest->vacuum_value_mtorr;
        }
    }
    echo "   Success.\n";

} catch (\Exception $e) {
    echo "\nCRITICAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
