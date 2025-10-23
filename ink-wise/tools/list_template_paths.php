<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Template;
use App\Support\ImageResolver;

$templates = Template::select('id','name','front_image','back_image','preview')->get();

foreach ($templates as $template) {
    echo "ID: {$template->id}\n";
    echo "Name: {$template->name}\n";
    echo "Front: {$template->front_image}\n";
    echo 'Front URL: ' . ImageResolver::url($template->front_image) . "\n";
    echo "Back: {$template->back_image}\n";
    echo 'Back URL: ' . ImageResolver::url($template->back_image) . "\n";
    echo "Preview: {$template->preview}\n";
    echo 'Preview URL: ' . ImageResolver::url($template->preview) . "\n";
    echo "------\n";
}
