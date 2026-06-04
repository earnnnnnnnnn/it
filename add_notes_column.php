<?php
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE borrowings ADD COLUMN notes TEXT NULL AFTER reason");
    echo "Column 'notes' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'notes' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
