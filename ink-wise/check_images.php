<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = App\Models\Template::find(75);
$product = App\Models\Product::find(36);

echo "Template images:\n";
echo "front_image: " . ($template->front_image ?? 'null') . "\n";
echo "back_image: " . ($template->back_image ?? 'null') . "\n";
echo "preview: " . ($template->preview ?? 'null') . "\n";
echo "svg_path: " . ($template->svg_path ?? 'null') . "\n";
echo "\n";
echo "Product images:\n";
echo "image: " . ($product->image ?? 'null') . "\n";
if ($product->images) {
    echo "images->front: " . ($product->images->front ?? 'null') . "\n";
    echo "images->back: " . ($product->images->back ?? 'null') . "\n";
}
