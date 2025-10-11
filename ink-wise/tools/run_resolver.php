<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Support\ImageResolver;

$path = 'products/N0fSUaFyWDIjzAwbosM5n8gkKt8nVbyqkY59GWcr.png';
$url = ImageResolver::url($path);

echo "Resolved URL: $url\n";
