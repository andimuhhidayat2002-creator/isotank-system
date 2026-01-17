<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Get MAX ID only (Fast, no sorting of heavy columns)
$maxId = \App\Models\ActivityUpload::max('id');

if (!$maxId) {
    echo "No uploads found.\n";
    exit;
}

// 2. Fetch single record by ID (No sorting triggers)
$latest = \App\Models\ActivityUpload::find($maxId);

echo "Latest Upload ID: " . $latest->id . "\n";
echo "Error Count: " . $latest->error_count . "\n";
echo "Date: " . $latest->created_at . "\n";
echo "First 5 Errors:\n";

$errors = $latest->error_details ?? [];
if (is_string($errors)) {
    $errors = json_decode($errors, true);
}
if (empty($errors)) {
    echo "No details log found.\n";
} else {
    // Show first 5
    print_r(array_slice($errors, 0, 5));
}
