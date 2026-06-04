<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>
