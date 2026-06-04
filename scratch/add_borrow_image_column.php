<?php
require_once 'c:/xampp/htdocs/it/config/db.php';

try {
    // Check if the column 'image' already exists in the 'borrowings' table
    $stmt = $pdo->query("SHOW COLUMNS FROM borrowings LIKE 'image'");
    $column = $stmt->fetch();
    
    if (!$column) {
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN image VARCHAR(255) DEFAULT NULL");
        echo "Column 'image' successfully added to 'borrowings' table!\n";
    } else {
        echo "Column 'image' already exists in 'borrowings' table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
