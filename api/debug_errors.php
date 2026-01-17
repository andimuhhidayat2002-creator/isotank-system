<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$latest = \App\Models\ActivityUpload::latest()->first();

if (!$latest) {
    echo "No uploads found.\n";
} else {
    echo "Latest Upload ID: " . $latest->id . "\n";
    echo "Error Count: " . $latest->error_count . "\n";
    echo "First 5 Errors:\n";
    print_r(array_slice($latest->error_details ?? [], 0, 5));
}
