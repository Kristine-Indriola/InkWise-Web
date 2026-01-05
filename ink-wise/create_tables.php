<?php
echo "Creating sessions table...\n";
try {
    $pdo = new PDO('sqlite:database/database.sqlite');

    // Create sessions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR PRIMARY KEY,
        user_id INTEGER NULL,
        ip_address VARCHAR NULL,
        user_agent TEXT NULL,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    )");

    // Create cache table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cache (
        key VARCHAR PRIMARY KEY,
        value TEXT,
        expiration INTEGER
    )");

    // Create cache_locks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cache_locks (
        key VARCHAR PRIMARY KEY,
        owner VARCHAR,
        expiration INTEGER
    )");

    // Create migrations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration VARCHAR NOT NULL,
        batch INTEGER NOT NULL
    )");

    echo "Essential tables created successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
