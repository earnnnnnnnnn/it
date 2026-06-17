<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['serials'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create import record
    $stmt_import = $pdo->prepare("INSERT INTO stock_imports (admin_id, reason, note) VALUES (?, ?, ?)");
    $stmt_import->execute([$_SESSION['user_id'], $data['reason'], $data['note'] ?? null]);
    $import_id = $pdo->lastInsertId();

    // 2. Create import item
    $qty = count($data['serials']);
    $stmt_item = $pdo->prepare("INSERT INTO stock_import_items (import_id, product_id, qty) VALUES (?, ?, ?)");
    $stmt_item->execute([$import_id, $data['product_id'], $qty]);
    $item_id = $pdo->lastInsertId();

    // 3. Insert serials into both product_serials and stock_import_serials
    $stmt_ps = $pdo->prepare("INSERT INTO product_serials (product_id, serial_code) VALUES (?, ?)");
    $stmt_sis = $pdo->prepare("INSERT INTO stock_import_serials (import_item_id, serial_code) VALUES (?, ?)");

    foreach ($data['serials'] as $code) {
        // Check if serial exists
        $stmt_check = $pdo->prepare("SELECT id FROM product_serials WHERE serial_code = ?");
        $stmt_check->execute([$code]);
        if ($stmt_check->fetch()) {
             throw new Exception("Serial $code มีอยู่ในระบบแล้ว");
        }

        $stmt_ps->execute([$data['product_id'], $code]);
        $stmt_sis->execute([$item_id, $code]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
