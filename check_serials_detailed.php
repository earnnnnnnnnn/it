<?php
require_once 'config/db.php';

echo "=== SERIALS WITH ACTIVE BORROWINGS BUT STATUS AVAILABLE ===\n";
$stmt = $pdo->query("
    SELECT ps.id, ps.serial_code, ps.status, b.id as borrow_id, b.borrowed_at, b.returned_at,
           TRIM(CONCAT(u.firstname, ' ', IFNULL(u.lastname, ''))) as borrower_name
    FROM product_serials ps
    JOIN borrowings b ON ps.id = b.serial_id
    LEFT JOIN users u ON b.borrower_id = u.id
    WHERE b.returned_at IS NULL AND ps.status = 'available'
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No inconsistencies found. All active borrowings have status != 'available'.\n";
} else {
    print_r($rows);
}

echo "\n=== ALL SERIALS STATUS SUMMARY ===\n";
$stmt2 = $pdo->query("SELECT status, COUNT(*) as count FROM product_serials GROUP BY status");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>
