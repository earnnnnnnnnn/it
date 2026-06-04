<?php
require_once '../config/db.php';

$code = $_GET['code'] ?? '';

if (!$code) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT ps.*, p.name, p.brand, p.model, p.category, p.image, p.price,
                              b.asset_number, b.borrowed_at, b.returned_at, b.building, b.floor, b.department, b.reason, TRIM(CONCAT(u.firstname, ' ', IFNULL(u.lastname, ''))) as borrower_name
                       FROM product_serials ps 
                       JOIN products p ON ps.product_id = p.id 
                       LEFT JOIN (
                           SELECT serial_id, asset_number, borrowed_at, returned_at, borrower_id, building, floor, department, reason 
                           FROM borrowings 
                           WHERE id IN (SELECT MAX(id) FROM borrowings GROUP BY serial_id)
                       ) b ON ps.id = b.serial_id
                       LEFT JOIN users u ON b.borrower_id = u.id
                       WHERE ps.serial_code = ?");
$stmt->execute([$code]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    // If there is an active borrowing (borrowed_at is set, returned_at is null), override status to 'borrowed'
    if (!empty($data['borrowed_at']) && empty($data['returned_at'])) {
        $data['status'] = 'borrowed';
    }
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false]);
}
?>
