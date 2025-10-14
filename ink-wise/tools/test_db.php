<?php

try {
    $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=laravels;charset=utf8mb4';
    $user = 'root';
    $pass = '';
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "OK\n";
} catch (PDOException $e) {
    echo "ERR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
