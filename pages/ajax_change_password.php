<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง']);
    exit;
}

if (strlen($new_password) < 4) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน']);
    exit;
}

try {
    // Fetch current user
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
        exit;
    }

    // Verify current password (support both hashed and plain text)
    $password_valid = false;
    if (password_verify($current_password, $user['password'])) {
        $password_valid = true;
    } elseif ($current_password === $user['password']) {
        // Plain text fallback
        $password_valid = true;
    }

    if (!$password_valid) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$hashed_password, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
