<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(67);
if ($template) {
    echo "Template exists: " . $template->name . "\n";
    echo "Status: " . $template->status . "\n";
    echo "Design data length: " . strlen($template->design ?? '') . "\n";
} else {
    echo "Template not found\n";
}