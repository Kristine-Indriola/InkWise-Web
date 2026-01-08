<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templates = App\Models\Template::orderByDesc('id')->limit(10)->get(['id','name','preview','preview_front','front_image']);
foreach ($templates as $template) {
    echo $template->id . ' | ' . ($template->name ?? 'unnamed') . ' | ' . ($template->preview ?? 'null') . PHP_EOL;
}
