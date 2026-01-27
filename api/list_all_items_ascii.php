<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::orderBy('order')->get();

$out = "";
foreach($items as $i) {
    $out .= "Label: [" . $i->label . "] | Code: [" . $i->code . "] | Cat: [" . $i->category . "]\n";
}
file_put_contents('all_items_ascii.txt', $out);
