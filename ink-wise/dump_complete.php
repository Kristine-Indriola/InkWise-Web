<?php

$data = json_decode(file_get_contents('storage/app/public/templates/assets/template_e7643598-1730-4da5-bf78-6e65b0758a2e.json'), true);
file_put_contents('template_25_complete.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Complete data saved to template_25_complete.json" . PHP_EOL;
