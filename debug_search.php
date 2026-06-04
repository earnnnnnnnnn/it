<?php
require_once 'config/db.php';
$q = '7440-001-0001-60-0096';
$search = "%$q%";

echo "Searching for: $search\n";

$stmt = $pdo->prepare("SELECT ps.serial_code, ps.status, b.asset_number
                       FROM product_serials ps 
                       LEFT JOIN (
                           SELECT serial_id, asset_number 
                           FROM borrowings 
                           WHERE id IN (SELECT MAX(id) FROM borrowings GROUP BY serial_id)
                       ) b ON ps.id = b.serial_id
                       WHERE b.asset_number LIKE ? OR ps.serial_code LIKE ?");
$stmt->execute([$search, $search]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Results found: " . count($results) . "\n";
print_r($results);

echo "\nFull borrowings table for this asset number:\n";
$stmt = $pdo->prepare("SELECT * FROM borrowings WHERE asset_number LIKE ?");
$stmt->execute([$search]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
