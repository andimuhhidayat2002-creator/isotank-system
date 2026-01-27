<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::where('label', 'like', '%regu%')->get();
foreach($items as $i) {
    echo "ID: " . $i->id . " | Label: [" . $i->label . "] | Code: [" . $i->code . "] | Active: " . ($i->is_active ? 'Y' : 'N') . "\n";
}
