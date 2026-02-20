<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "Bootstrap successful\n";

use App\Models\MaintenanceJob;

$count = MaintenanceJob::count();
echo "Total Maintenance Jobs: $count\n";

$jobs = MaintenanceJob::latest()->limit(20)->get(['id', 'isotank_id', 'source_item', 'status']);
foreach ($jobs as $job) {
    echo "ID: {$job->id}, ISO_ID: {$job->isotank_id}, Item: {$job->source_item}, Status: {$job->status}\n";
}
