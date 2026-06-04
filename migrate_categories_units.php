<?php
require_once 'config/db.php';

try {

    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            sort_order INT DEFAULT 0
        )
    ");

    // Create units table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL,
            sort_order INT DEFAULT 0
        )
    ");

    $pdo->beginTransaction();

    // Insert default categories
    $default_categories = ['IT Gadget', 'Office Supplies', 'Network Equipment', 'IT', 'Office', 'Network'];
    $stmt_cat = $pdo->prepare("INSERT IGNORE INTO categories (name, sort_order) VALUES (?, ?)");
    foreach ($default_categories as $index => $cat) {
        $stmt_cat->execute([$cat, $index + 1]);
    }

    // Insert default units
    $default_units = ['ชิ้น', 'ตัว', 'เครื่อง', 'ชุด', 'กล่อง', 'อัน', 'ม้วน'];
    $stmt_unit = $pdo->prepare("INSERT IGNORE INTO units (name, sort_order) VALUES (?, ?)");
    foreach ($default_units as $index => $unit) {
        $stmt_unit->execute([$unit, $index + 1]);
    }

    $pdo->commit();
    echo "Migration completed successfully!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage();
}
?>
