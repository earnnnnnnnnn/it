<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert default types if the table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM product_types")->fetchColumn();
    if ($count == 0) {
        $existing_types = $pdo->query("SELECT DISTINCT name FROM products WHERE name IS NOT NULL AND name != '' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        $default_types = ['Keyboard', 'Mouse', 'จอคอมพิวเตอร์'];
        $product_types = array_unique(array_merge($default_types, $existing_types));
        
        $stmt = $pdo->prepare("INSERT INTO product_types (name, sort_order) VALUES (?, ?)");
        foreach ($product_types as $index => $type) {
            $stmt->execute([$type, $index + 1]);
        }
        echo "Product types migrated successfully.\n";
    } else {
        echo "Product types table already has data.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
