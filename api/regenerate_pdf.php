<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\InspectionLog;
use App\Services\PdfGenerationService;

echo "--- PDF REGENERATION TOOL ---\n";

// Get latest 5 logs
$logs = InspectionLog::with('isotank')->latest()->take(5)->get();

foreach ($logs as $log) {
    echo "ID: {$log->id} | ISO: " . ($log->isotank->iso_number ?? 'N/A') . " | Type: {$log->inspection_type} | Date: {$log->inspection_date}\n";
}

// Just pick the very latest one for now to test
$targetLog = $logs->first();

if (!$targetLog) {
    die("No inspection logs found.\n");
}

echo "\nRegenerating PDF for Log ID: {$targetLog->id} ({$targetLog->isotank->iso_number})...\n";

try {
    $service = new PdfGenerationService();
    $path = "";
    
    if (str_contains($targetLog->inspection_type, 'incoming')) {
        $path = $service->generateIncomingPdf($targetLog);
    } else {
        $path = $service->generateOutgoingPdf($targetLog);
    }
    
    echo "SUCCESS! New PDF generated at: {$path}\n";
    echo "Check this PDF in the app/admin panel now.\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
