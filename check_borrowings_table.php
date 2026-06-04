<?php
require_once 'config/db.php';

echo "=== BORROWINGS TABLE STRUCTURE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM borrowings");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n=== CHECKING FOR MISSING COLUMNS ===\n";
$required_cols = ['asset_number', 'building', 'floor', 'department', 'image'];
$existing_cols = array_column($columns, 'Field');

foreach ($required_cols as $col) {
    if (!in_array($col, $existing_cols)) {
        echo "MISSING: $col\n";
    } else {
        echo "OK: $col\n";
    }
}
?>
