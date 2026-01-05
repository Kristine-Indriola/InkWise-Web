<?php
echo "Testing database connection...\n";
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    echo "Database connection successful\n";

    $result = $pdo->query('SELECT name FROM sqlite_master WHERE type="table" AND name="sessions"');
    if ($result->fetch()) {
        echo "Sessions table exists\n";
    } else {
        echo "Sessions table does not exist\n";
    }

    // Try a simple query on sessions table
    $count = $pdo->query('SELECT COUNT(*) FROM sessions')->fetchColumn();
    echo "Sessions table has $count records\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
