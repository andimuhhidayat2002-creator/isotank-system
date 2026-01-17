<?php

use App\Models\User;
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('email', 'yard@isotank.com')->first();
if ($user) {
    $user->role = 'yard_operator';
    $user->save();
    echo "User role updated to: " . $user->role . PHP_EOL;
} else {
    echo "User not found." . PHP_EOL;
}
