<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Template::find(2);
echo 'status: ' . $t->status . PHP_EOL;
echo 'front_image: ' . $t->front_image . PHP_EOL;
echo 'preview: ' . $t->preview . PHP_EOL;
?>
