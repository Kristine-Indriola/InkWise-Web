<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int)($argv[1] ?? 0);
$template = App\Models\Template::find($id);
if (!$template) {
    fwrite(STDERR, "Template not found\n");
    exit(1);
}

$design = $template->design;
if (!$design) {
    echo "<no design field>\n";
    exit(0);
}

echo substr($design, 0, 400), "\n";
