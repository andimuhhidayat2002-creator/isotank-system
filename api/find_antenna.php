<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InspectionItem;

$items = InspectionItem::where('label', 'like', '%GPS%')->get();
foreach ($items as $item) {
    echo "ID: {$item->id} | Code: '{$item->code}' | Label: '{$item->label}' | Category: '{$item->category}'\n";
}
