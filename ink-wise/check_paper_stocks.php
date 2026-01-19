<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$paperStocks = \App\Models\ProductPaperStock::where('product_id', 64)->with('material')->get();
echo 'ProductPaperStock records for product 64:' . PHP_EOL;
foreach ($paperStocks as $ps) {
    echo 'ID: ' . $ps->id . ', Name: ' . $ps->name . ', Material: ' . ($ps->material->material_name ?? 'null') . ', Material ID: ' . $ps->material_id . PHP_EOL;
}
?>