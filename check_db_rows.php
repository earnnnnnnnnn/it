<?php
require 'config/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM product_serials');
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
