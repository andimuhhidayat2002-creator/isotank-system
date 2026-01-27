<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::where('label', 'like', '%Pressure regulator%')->get();
if ($items->isEmpty()) {
    $items = App\Models\InspectionItem::where('label', 'like', '%regulator%')->get();
}

foreach($items as $i) {
    echo "Label: [" . $i->label . "] | Code: [" . $i->code . "] | Cat: [" . $i->category . "]\n";
}
