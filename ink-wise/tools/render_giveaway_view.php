<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Arr;

$p = Product::with(['uploads','images','template'])->where('product_type','Giveaway')->first();
if (!$p) {
    echo "No giveaway product found\n";
    exit(1);
}

$html = view('customer.orderflow.giveaways', ['products' => collect([$p])])->render();

// find the giveaway-card element
if (preg_match('/<article class="giveaway-card"[\s\S]*?<\/article>/', $html, $m)) {
    echo $m[0];
} else {
    echo "Could not extract article HTML\n";
}

