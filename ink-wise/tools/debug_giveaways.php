<?php
require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager as Capsule;

// Bootstrap a minimal app container to use DB facade
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = DB::select("SELECT p.id,p.name,p.image,t.preview as template_preview, pi.front, pi.preview, pi.back FROM products p LEFT JOIN templates t ON t.id = p.template_id LEFT JOIN product_images pi ON pi.product_id = p.id WHERE p.product_type = 'Giveaway' LIMIT 10");
foreach ($rows as $r) {
    echo "ID: {$r->id}\n";
    echo "Name: {$r->name}\n";
    echo "image: {$r->image}\n";
    echo "template_preview: {$r->template_preview}\n";
    echo "images front: {$r->front}, preview: {$r->preview}, back: {$r->back}\n";
    echo "-------------------------------\n";
}

