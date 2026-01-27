<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::where('label', 'like', '%ESDV%')
    ->get();

foreach($items as $i) {
    echo "Label: " . $i->label . " | Code: " . $i->code . " | Category: " . $i->category . "\n";
}
