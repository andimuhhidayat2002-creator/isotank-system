<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$columns = Schema::getColumnListing('inspection_logs');
echo "Columns in inspection_logs:\n";
print_r($columns);

echo "\nChecking if filling_status_code exists: " . (in_array('filling_status_code', $columns) ? 'YES' : 'NO') . "\n";
echo "Checking if filling_status_desc exists: " . (in_array('filling_status_desc', $columns) ? 'YES' : 'NO') . "\n";
