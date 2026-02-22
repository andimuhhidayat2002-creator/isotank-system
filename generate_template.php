<?php

// Script to generate a Calibration Master Template matching the system requirements.

$fileName = 'calibration_template.csv';

$columns = ['Isotank Number', 'Location'];

// Define standard structure from CalibrationMasterController.php
$struct = [
    'PG' => ['Main'],
    'PSV' => [1, 2, 3, 4],
    'PRV' => [1, 2, 3, 4, 5, 6, 7]
];

// Build Header
foreach ($struct as $type => $positions) {
    foreach ($positions as $pos) {
        // Short codes for header
        $p = $type . ($pos === 'Main' ? '' : $pos); // e.g., PG, PSV1
        $columns[] = "$p SN";
        $columns[] = "$p Cert";
        if ($type !== 'PG') $columns[] = "$p Press";
        $columns[] = "$p Cal Date";
        $columns[] = "$p Exp";
    }
}

// Open file for writing
$file = fopen($fileName, 'w');

// Add BOM for Excel UTF-8 recognition
fwrite($file, "\xEF\xBB\xBF");

// Use COMMA (,) as delimiter for the template (standard)
fputcsv($file, $columns, ',');

// Add a sample row
$sampleRow = ['JSDU123456-7', 'LYG'];
foreach ($struct as $type => $positions) {
    foreach ($positions as $pos) {
        $sampleRow[] = "SN" . rand(1000, 9999); // SN
        $sampleRow[] = "CERT" . rand(1000, 9999); // Cert
        if ($type !== 'PG') $sampleRow[] = ($type === 'PSV' ? '0.44' : '0.1'); // Press
        $sampleRow[] = date('Y-m-d'); // Cal Date
        $sampleRow[] = date('Y-m-d', strtotime('+1 year')); // Exp
    }
}


// Corrected sample row write:
fputcsv($file, $sampleRow, ',');

fclose($file);

echo "✅ Template generated: $fileName\n";
