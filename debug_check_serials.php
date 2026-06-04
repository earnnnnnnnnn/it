<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM product_serials WHERE id IN (51, 52, 53, 55)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
