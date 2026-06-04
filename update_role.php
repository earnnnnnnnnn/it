<?php
require_once __DIR__ . '/config/db.php';

// First, ensure the role column supports ADMIN (and optionally SUPERADMIN)
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'USER'");
} catch (Exception $e) {}

$stmt = $pdo->prepare("UPDATE users SET role = 'SUPERADMIN' WHERE firstname LIKE '%มามี่%' OR lastname LIKE '%โปะโกะ%' OR firstname LIKE '%อามามี่%' OR username LIKE '%มามี่%'");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " users to SUPERADMIN.";
?>
