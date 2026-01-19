<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking material inventory levels:\n";

$materials = \App\Models\Material::with('inventory')->get();

foreach ($materials as $material) {
    $inventoryStock = $material->inventory?->stock_level ?? 'N/A';
    $materialStock = $material->stock_qty ?? 'N/A';
    echo "- {$material->material_name}: Material Stock: {$materialStock}, Inventory Stock: {$inventoryStock}\n";
}