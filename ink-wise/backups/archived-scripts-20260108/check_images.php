<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Template;

echo "Checking template images...\n\n";

$templates = Template::where('status', '!=', 'uploaded')->get();

foreach ($templates as $t) {
    $img = $t->front_image ?? 'none';
    $exists = Storage::disk('public')->exists($img) ? 'YES' : 'NO ';
    echo "{$t->id}: {$img} - {$exists}\n";
}
