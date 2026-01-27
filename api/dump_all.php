<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\InspectionItem::all();
foreach($items as $i) {
    file_put_contents('raw_dump.txt', $i->id . "|" . $i->label . "|" . $i->code . "|" . $i->category . "\n", FILE_APPEND);
}
