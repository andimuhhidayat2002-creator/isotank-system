<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::all();
foreach($items as $i) {
    if (str_contains(strtolower($i->label), 'pressure') && str_contains(strtolower($i->label), 'esdv')) {
        echo "Label: [" . $i->label . "] | Code: [" . $i->code . "] | Cat: [" . $i->category . "]\n";
    }
}
