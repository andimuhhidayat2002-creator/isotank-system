<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InspectionJob;

$jobs = InspectionJob::with('isotank')->latest()->take(5)->get();
foreach ($jobs as $job) {
    echo "Job ID: {$job->id} | Isotank: {$job->isotank->iso_number} | Category: {$job->isotank->tank_category}\n";
}
