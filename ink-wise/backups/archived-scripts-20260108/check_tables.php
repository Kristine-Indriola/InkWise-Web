<?php
echo "Checking all tables in database...\n";
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $result = $pdo->query('SELECT name FROM sqlite_master WHERE type="table"');
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables found:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
