<?php
require_once 'config/db.php';

try {
    // Check if column already exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetchAll();
    
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') NOT NULL DEFAULT 'active' AFTER role");
        echo "✅ เพิ่มคอลัมน์ 'status' ในตาราง users สำเร็จ\n";
    } else {
        echo "ℹ️ คอลัมน์ 'status' มีอยู่แล้ว\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
