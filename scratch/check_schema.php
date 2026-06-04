<?php
require_once 'config/db.php';

try {
    echo "--- TABLES ---\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);

    echo "\n--- REASONS SCHEMA ---\n";
    $schema = $pdo->query("DESCRIBE reasons")->fetchAll(PDO::FETCH_ASSOC);
    print_r($schema);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
