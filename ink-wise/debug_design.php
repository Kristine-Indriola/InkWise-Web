<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = \App\Models\Template::find(19);
$design = json_decode($template->design, true);

echo 'Design data for template 19:' . PHP_EOL;
print_r($design);