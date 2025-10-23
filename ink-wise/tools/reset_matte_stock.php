<?php

use App\Models\Inventory;
use App\Models\Material;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$material = Material::query()->whereRaw('LOWER(material_name) = ?', ['matte'])->first();

if (!$material) {
    echo "Matte material not found.\n";
    exit(0);
}

$material->stock_qty = 80;
$material->save();

$inventory = Inventory::query()->where('material_id', $material->material_id)->first();
if ($inventory) {
    $inventory->stock_level = 80;
    $inventory->save();
}

echo "Matte stock reset to 80.\n";
