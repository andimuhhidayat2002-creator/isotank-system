<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$isotanks = DB::table('master_isotanks')
    ->where('location', 'SMGRS')
    ->get(['iso_number', 'filling_status_code', 'filling_status_desc']);

echo "ISOTANKS AT SMGRS:\n";
echo str_repeat('-', 80) . "\n";
printf("%-15s | %-25s | %-30s\n", "ISO Number", "Filling Status Code", "Filling Status Desc");
echo str_repeat('-', 80) . "\n";

foreach ($isotanks as $isotank) {
    printf("%-15s | %-25s | %-30s\n", 
        $isotank->iso_number, 
        $isotank->filling_status_code ?? 'NULL',
        $isotank->filling_status_desc ?? 'NULL'
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total: " . $isotanks->count() . " isotanks\n";
