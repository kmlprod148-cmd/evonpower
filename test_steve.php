<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steve = app(\App\Services\SteVeHttpClientService::class);
try {
    $result = $steve->listChargePoints();
    echo "SteVeHttpClientService result:\n";
    var_dump(array_keys($result));
    var_dump($result['success'] ?? false);
} catch (\Exception $e) {
    echo "SteVeHttpClientService exception: " . $e->getMessage() . "\n";
}

echo "\n-------------------\n";

$steveLegacy = app(\App\Services\SteveService::class);
try {
    $result2 = $steveLegacy->getChargePoints();
    echo "SteveService result:\n";
    var_dump(is_array($result2));
} catch (\Exception $e) {
    echo "SteveService exception: " . $e->getMessage() . "\n";
}
