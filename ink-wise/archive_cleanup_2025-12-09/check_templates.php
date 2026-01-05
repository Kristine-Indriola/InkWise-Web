<?php
require 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$templates = \App\Models\Template::where('status', 'uploaded')->take(5)->get();

foreach ($templates as $template) {
    echo "ID: {$template->id}\n";
    echo "Name: {$template->name}\n";
    echo "Front Image: {$template->front_image}\n";
    echo "Back Image: {$template->back_image}\n";
    echo "Preview: {$template->preview}\n";
    echo "Resolved Front: " . \App\Support\ImageResolver::url($template->front_image) . "\n";
    echo "Resolved Preview: " . \App\Support\ImageResolver::url($template->preview) . "\n";
    echo "---\n";
}
