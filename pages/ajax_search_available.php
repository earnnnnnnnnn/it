<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$q = $_GET['q'] ?? '';

if (empty($q)) {
    echo json_encode([]);
    exit;
}

$search = "%$q%";
$stmt = $pdo->prepare("SELECT ps.serial_code, p.name, p.brand, p.model, p.category, p.image, p.price, ps.status, b.asset_number, b.borrowed_at, b.returned_at,
                              b.building, b.floor, b.department, b.reason, TRIM(CONCAT(u.firstname, ' ', IFNULL(u.lastname, ''))) as borrower_name
                       FROM product_serials ps 
                       JOIN products p ON ps.product_id = p.id 
                       LEFT JOIN (
                           SELECT serial_id, asset_number, borrowed_at, returned_at, borrower_id, building, floor, department, reason 
                           FROM borrowings 
                           WHERE id IN (SELECT MAX(id) FROM borrowings GROUP BY serial_id)
                       ) b ON ps.id = b.serial_id
                       LEFT JOIN users u ON b.borrower_id = u.id
                       WHERE (p.name LIKE ? OR p.sku LIKE ? OR ps.serial_code LIKE ? OR p.brand LIKE ? OR p.model LIKE ? OR b.asset_number LIKE ?)
                       LIMIT 15");
$stmt->execute([$search, $search, $search, $search, $search, $search]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as &$item) {
    if (!empty($item['borrowed_at']) && empty($item['returned_at'])) {
        $item['status'] = 'borrowed';
    }
}
unset($item);

echo json_encode($results);
