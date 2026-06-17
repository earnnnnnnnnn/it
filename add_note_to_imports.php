<?php
require_once __DIR__ . '/config/db.php';
try {
    $pdo->exec("ALTER TABLE stock_imports ADD COLUMN note TEXT DEFAULT NULL AFTER reason;");
    echo "Column 'note' added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
