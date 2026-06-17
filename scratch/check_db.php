<?php
require_once '../config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'rental_duration'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Type: " . $col['Type'] . "\n";

$stmt2 = $pdo->query("SELECT id, name, rental_duration FROM products WHERE rental_duration IS NOT NULL AND rental_duration != '' AND rental_duration != '0' LIMIT 5");
$rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Data:\n";
print_r($rows);
