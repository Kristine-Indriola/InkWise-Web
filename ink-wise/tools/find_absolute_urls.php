<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('templates')->select('id','front_image','back_image','preview')->get();
foreach ($rows as $r) {
    foreach (['front_image','back_image','preview'] as $c) {
        if ($r->$c && preg_match('#^https?://#i', $r->$c)) {
            echo "Template {$r->id} has absolute URL in $c: {$r->$c}\n";
        }
    }
}
