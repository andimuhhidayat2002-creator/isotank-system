<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MaintenanceJob;
use Illuminate\Support\Facades\DB;

$total = MaintenanceJob::count();
$statuses = MaintenanceJob::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Total Maintenance Jobs: $total\n";
echo "Statuses:\n";
foreach ($statuses as $s) {
    echo "- {$s->status}: {$s->count}\n";
}

$first5 = MaintenanceJob::with('isotank')->limit(5)->get();
echo "\nFirst 5 Jobs:\n";
foreach ($first5 as $j) {
    echo "ID: {$j->id}, ISO: " . ($j->isotank->iso_number ?? 'N/A') . ", Status: {$j->status}\n";
}
