<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
\ = App\Models\CustomerReview::find(9);
echo 'Found: ' . (\ ? 'YES' : 'NO') . PHP_EOL;
if (\) {
    echo 'template_id: ' . \->template_id . PHP_EOL;
    echo 'design_svg length: ' . strlen(\->design_svg ?? '') . PHP_EOL;
    echo 'First 500 chars: ' . substr(\->design_svg, 0, 500) . PHP_EOL;
}
