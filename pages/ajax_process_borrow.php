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

    // Handle borrower (ID or Name)
    $borrower_id = $data['borrower_id'];
    if (!is_numeric($borrower_id)) {
        // It's a name, try to find existing user or create new
        $stmt_user = $pdo->prepare("SELECT id FROM users WHERE CONCAT(firstname, ' ', lastname) = ?");
        $stmt_user->execute([$borrower_id]);
        $user = $stmt_user->fetch();
        
        if ($user) {
            $borrower_id = $user['id'];
        } else {
            // Create new user
            $parts = explode(' ', $borrower_id, 2);
            $firstname = $parts[0];
            $lastname = isset($parts[1]) ? $parts[1] : '';
            $username = strtolower(str_replace(' ', '', $firstname)) . rand(100, 999);
            $new_email = $username . '@it-system.com';
            
            $stmt_create = $pdo->prepare("INSERT INTO users (username, firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?, 'USER')");
            $hashed_password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt_create->execute([$username, $firstname, $lastname, $new_email, $hashed_password]);
            $borrower_id = $pdo->lastInsertId();
        }
    }

    // Decode and save borrow condition image if provided
    $filename = null;
    if (!empty($data['image'])) {
        $img_data = $data['image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $img_data, $type)) {
            $img_data = substr($img_data, strpos($img_data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif, webp

            if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $img_data = base64_decode($img_data);
                if ($img_data !== false) {
                    $filename = 'borrows/borrow_' . time() . '_' . rand(1000, 9999) . '.' . $type;
                    if (!is_dir('../assets/images/borrows')) {
                        mkdir('../assets/images/borrows', 0777, true);
                    }
                    file_put_contents('../assets/images/' . $filename, $img_data);
                }
            }
        }
    }

    $stmt_borrow = $pdo->prepare("INSERT INTO borrowings (serial_id, borrower_id, asset_number, building, floor, department, approver_name, reason, notes, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_update = $pdo->prepare("UPDATE product_serials SET status = 'borrowed' WHERE id = ?");

    foreach ($data['serials'] as $code) {
        // Find serial ID and check availability
        $stmt_find = $pdo->prepare("SELECT id, status FROM product_serials WHERE serial_code = ?");
        $stmt_find->execute([$code]);
        $serial = $stmt_find->fetch();

        if ($serial) {
            if ($serial['status'] !== 'available') {
                throw new Exception("สินค้าที่มี Serial Code " . $code . " ถูกเบิกไปแล้ว หรือไม่พร้อมใช้งาน");
            }
            $stmt_borrow->execute([
                $serial['id'], 
                $borrower_id, 
                $data['asset_number'] ?? '', 
                $data['building'] ?? '',
                $data['floor'] ?? '',
                $data['department'] ?? '',
                $_SESSION['user_name'], 
                $data['reason'],
                $data['notes'] ?? null,
                $filename
            ]);
            $stmt_update->execute([$serial['id']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
