<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

if (empty($q)) {
    echo json_encode(['error' => 'กรุณาระบุคำค้นหา']);
    exit;
}

// 1. Try to find if $q matches an asset_number or serial_code in product_serials
$stmt = $pdo->prepare("SELECT ps.*, p.name, p.brand, p.model, p.image 
                       FROM product_serials ps
                       JOIN products p ON ps.product_id = p.id
                       WHERE ps.serial_code = ?");
$stmt->execute([$q]);
$item = $stmt->fetch();

if ($item) {
    // Fetch borrow history for this item
    $stmt_history = $pdo->prepare("SELECT b.borrowed_at as borrow_date, b.reason, u.firstname, u.lastname, u.username, u.image as user_image, b.image as condition_image
                                   FROM borrowings b
                                   JOIN users u ON b.borrower_id = u.id
                                   WHERE b.serial_id = ?
                                   ORDER BY b.borrowed_at DESC");
    $stmt_history->execute([$item['id']]);
    $history = $stmt_history->fetchAll();

    echo json_encode([
        'type' => 'item',
        'item' => [
            'name' => $item['name'],
            'brand' => $item['brand'],
            'model' => $item['model'],
            'serial_code' => $item['serial_code'],
            'image' => $item['image']
        ],
        'history' => $history
    ]);
    exit;
}

// 2. Try to find if $q matches a user (firstname, lastname, or username)
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE CONCAT(firstname, ' ', lastname) LIKE ? OR username LIKE ?");
$stmt_user->execute(["%$q%", "%$q%"]);
$user = $stmt_user->fetch();

if ($user) {
    // Fetch borrow history for this user
    $stmt_history = $pdo->prepare("SELECT b.borrowed_at as borrow_date, b.reason, p.name as product_name, p.brand, p.model, p.image as product_image, b.image as condition_image
                                   FROM borrowings b
                                   JOIN product_serials ps ON b.serial_id = ps.id
                                   JOIN products p ON ps.product_id = p.id
                                   WHERE b.borrower_id = ?
                                   ORDER BY b.borrowed_at DESC");
    $stmt_history->execute([$user['id']]);
    $history = $stmt_history->fetchAll();

    echo json_encode([
        'type' => 'user',
        'user' => [
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'username' => $user['username'],
            'image' => $user['image']
        ],
        'history' => $history
    ]);
    exit;
}

echo json_encode(['error' => 'ไม่พบข้อมูลครุภัณฑ์ หรือรายชื่อผู้เบิกนี้ในระบบ']);
?>
