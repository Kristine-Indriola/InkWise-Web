<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select('SHOW TABLES');
echo "Tables in database:\n";
foreach($tables as $table) {
    echo $table->{'Tables_in_laravels'} . "\n";
}