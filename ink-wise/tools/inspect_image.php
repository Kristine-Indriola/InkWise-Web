<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$path = __DIR__ . '/../storage/app/public/products/N0fSUaFyWDIjzAwbosM5n8gkKt8nVbyqkY59GWcr.png';
if (!file_exists($path)) {
    echo "File does not exist: $path\n";
    exit(1);
}

echo "Path: $path\n";
echo "Filesize: " . filesize($path) . " bytes\n";
echo "MD5: " . md5_file($path) . "\n";
$info = @getimagesize($path);
if ($info) {
    echo "MIME: " . ($info['mime'] ?? 'unknown') . "\n";
    echo "Width: " . ($info[0] ?? '?') . " px\n";
    echo "Height: " . ($info[1] ?? '?') . " px\n";
} else {
    echo "Not an image or getimagesize failed\n";
}

// Output first bytes
$h = fopen($path, 'rb');
$first = bin2hex(fread($h, 64));
fclose($h);
echo "First 64 bytes (hex): $first\n";
