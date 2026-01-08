<!DOCTYPE html>
<html>
<head>
    <title>Image URL Test</title>
</head>
<body>
    <h1>Testing Template Image URLs</h1>
    
    <?php
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    use App\Models\Template;
    use App\Support\ImageResolver;

    $templates = Template::take(5)->get();
    
    foreach ($templates as $template) {
        $front = $template->front_image ?? $template->preview;
        $resolvedUrl = ImageResolver::url($front);
        
        echo "<div style='margin: 20px; padding: 20px; border: 1px solid #ccc;'>";
        echo "<h3>Template: {$template->name} (ID: {$template->id})</h3>";
        echo "<p><strong>Database Path:</strong> " . ($front ?? 'null') . "</p>";
        echo "<p><strong>Resolved URL:</strong> " . $resolvedUrl . "</p>";
        echo "<p><strong>Full URL:</strong> <a href='{$resolvedUrl}' target='_blank'>http://127.0.0.1:8000{$resolvedUrl}</a></p>";
        echo "<img src='{$resolvedUrl}' alt='Preview' style='max-width: 300px; border: 1px solid #999;' onerror=\"this.parentElement.innerHTML += '<p style=color:red>❌ Image failed to load!</p>'\" onload=\"this.parentElement.innerHTML += '<p style=color:green>✅ Image loaded successfully!</p>'\">";
        echo "</div>";
    }
    ?>
</body>
</html>
