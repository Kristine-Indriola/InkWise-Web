<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\Customer\OrderFlowController(app()->make(\App\Services\OrderFlowService::class));
$response = $controller->debugGiveawayImages();

// $response is Illuminate\Http\JsonResponse
$data = $response->getData(true);
print_r($data);
