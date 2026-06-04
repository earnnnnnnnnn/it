<?php
require_once 'config/db.php';

try {
    // Check if columns exist first
    $stmt = $pdo->query("SHOW COLUMNS FROM borrowings LIKE 'asset_number'");
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "Adding missing columns to borrowings table...\n";
        
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN asset_number VARCHAR(100) AFTER borrower_id");
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN building VARCHAR(100) AFTER asset_number");
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN floor VARCHAR(100) AFTER building");
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN department VARCHAR(100) AFTER floor");
        $pdo->exec("ALTER TABLE borrowings ADD COLUMN image VARCHAR(255) AFTER reason");
        
        echo "✓ Columns added successfully!\n";
    } else {
        echo "✓ Columns already exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
