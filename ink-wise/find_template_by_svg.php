<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$result = DB::table('templates')
    ->select('id', 'name', 'metadata')
    ->get();

foreach ($result as $r) {
    $meta = json_decode($r->metadata, true) ?? [];
    $svgPath = $meta['svg_path'] ?? '';
    if (strpos($svgPath, '1e5d4799') !== false) {
        echo "ID: {$r->id}\n";
        echo "Name: {$r->name}\n";
        echo "SVG Path: {$svgPath}\n";
        echo "JSON Path: " . ($meta['json_path'] ?? 'N/A') . "\n";
        echo "\n";
    }
}
