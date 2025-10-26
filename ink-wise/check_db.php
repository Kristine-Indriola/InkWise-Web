<?php
require 'vendor/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=laravels', 'root', '');
echo 'Templates: ' . $pdo->query('SELECT COUNT(*) FROM templates')->fetchColumn() . PHP_EOL;
echo 'ProductUploads: ' . $pdo->query('SELECT COUNT(*) FROM product_uploads')->fetchColumn() . PHP_EOL;
$stmt = $pdo->query('SELECT id, template_id FROM product_uploads LIMIT 3');
while($row = $stmt->fetch()) {
    echo 'PU ID: ' . $row['id'] . ', Template ID: ' . $row['template_id'] . PHP_EOL;
}