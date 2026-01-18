<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

$items = \App\Models\MasterIsotankItemStatus::where('isotank_id', 644)->pluck('item_name');
echo "Count: " . $items->count() . "\n";
foreach($items as $item) {
    echo "- " . $item . "\n";
}
