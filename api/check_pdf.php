<?php
use App\Models\InspectionLog;

$log = InspectionLog::whereNotNull('pdf_path')->latest()->first();

if ($log) {
    echo "Found Log ID: " . $log->id . "\n";
    echo "PDF Path: " . $log->pdf_path . "\n";
    echo "Isotank: " . $log->isotank->iso_number . "\n";
} else {
    echo "No logs with PDF path found.\n";
}
