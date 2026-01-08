<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = $argv[1] ?? null;
if (!$path) {
    fwrite(STDERR, "Usage: php debug_image_resolver.php <path>\n");
    exit(1);
}

$url = App\Support\ImageResolver::url($path);

fwrite(STDOUT, $url . "\n");
