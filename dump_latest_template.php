<?php
// Simple helper script to dump latest template row for debugging
require __DIR__ . '/ink-wise/vendor/autoload.php';
$app = require __DIR__ . '/ink-wise/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('templates')->orderBy('id', 'desc')->first();
echo json_encode($row, JSON_PRETTY_PRINT);
