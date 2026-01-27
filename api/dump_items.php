<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InspectionItem;
use Illuminate\Support\Facades\DB;

$items = InspectionItem::all();
foreach ($items as $item) {
    $cats = json_encode($item->applicable_categories);
    echo "Code: {$item->code} | Label: {$item->label} | Category: {$item->category} | Categories: {$cats}\n";
}
