<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $columns = DB::select('DESCRIBE users');
    echo 'Users table columns:' . PHP_EOL;
    foreach($columns as $column) {
        echo '  ' . $column->Field . ' (' . $column->Type . ')' . PHP_EOL;
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
