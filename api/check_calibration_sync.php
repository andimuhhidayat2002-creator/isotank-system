<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING CALIBRATION DATA UPDATE ===\n\n";

// Check latest inspection log
echo "1. Latest Inspection Log (Calibration Data):\n";
$latestInspection = DB::table('inspection_logs')
    ->latest()
    ->first([
        'id', 
        'isotank_id',
        'inspection_date',
        'pressure_gauge_serial_number', 
        'pressure_gauge_calibration_date',
        'psv1_serial_number',
        'psv1_calibration_date',
        'psv2_serial_number',
        'psv2_calibration_date',
    ]);

if ($latestInspection) {
    echo json_encode($latestInspection, JSON_PRETTY_PRINT) . "\n\n";
    
    $isotankId = $latestInspection->isotank_id;
    
    // Check master calibration status for this isotank
    echo "2. Master Calibration Status for Isotank ID {$isotankId}:\n";
    $masterCalibration = DB::table('master_isotank_calibration_status')
        ->where('isotank_id', $isotankId)
        ->get([
            'item_name',
            'serial_number',
            'calibration_date',
            'valid_until',
            'status',
            'updated_at'
        ]);
    
    if ($masterCalibration->count() > 0) {
        echo json_encode($masterCalibration, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "NO CALIBRATION DATA FOUND IN MASTER TABLE!\n\n";
    }
    
    // Check if data should have been updated
    echo "3. Analysis:\n";
    if ($latestInspection->pressure_gauge_serial_number) {
        echo "- PG Serial in inspection: {$latestInspection->pressure_gauge_serial_number}\n";
        $pgMaster = $masterCalibration->firstWhere('item_name', 'pressure_gauge');
        if ($pgMaster) {
            echo "- PG Serial in master: {$pgMaster->serial_number}\n";
            if ($pgMaster->serial_number != $latestInspection->pressure_gauge_serial_number) {
                echo "  ❌ MISMATCH! Master not updated!\n";
            } else {
                echo "  ✅ Match!\n";
            }
        } else {
            echo "  ❌ NOT FOUND in master table!\n";
        }
    }
    
    if ($latestInspection->psv1_serial_number) {
        echo "- PSV1 Serial in inspection: {$latestInspection->psv1_serial_number}\n";
        $psv1Master = $masterCalibration->firstWhere('item_name', 'psv1');
        if ($psv1Master) {
            echo "- PSV1 Serial in master: {$psv1Master->serial_number}\n";
            if ($psv1Master->serial_number != $latestInspection->psv1_serial_number) {
                echo "  ❌ MISMATCH! Master not updated!\n";
            } else {
                echo "  ✅ Match!\n";
            }
        } else {
            echo "  ❌ NOT FOUND in master table!\n";
        }
    }
    
} else {
    echo "No inspection logs found!\n";
}

echo "\n=== END ===\n";
