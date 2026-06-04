<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (($_SESSION['user_role'] ?? 'USER') !== 'SUPERADMIN') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
    exit;
}

$action = $_POST['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    // Helper function for image upload
    function uploadUserImage($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return 'default_user.png';
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
            return 'default_user.png';
        }
        $filename = 'users/user_' . time() . '_' . rand(100, 999) . '.' . $ext;
        
        if (!is_dir('../assets/images/users')) {
            mkdir('../assets/images/users', 0777, true);
        }
        
        $target = '../assets/images/' . $filename;
        move_uploaded_file($file['tmp_name'], $target);
        return $filename;
    }

    // ===== ADD USER =====
    if ($action === 'add') {
        if (empty($_POST['username']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email']) || empty($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
            exit;
        }

        // Check duplicate email or username
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->execute([$_POST['email'], $_POST['username']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'อีเมลหรือชื่อผู้ใช้นี้มีในระบบแล้ว']);
            exit;
        }

        $image = uploadUserImage($_FILES['image'] ?? null);

        $stmt = $pdo->prepare("INSERT INTO users (username, firstname, lastname, email, password, role, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['username'],
            $_POST['firstname'],
            $_POST['lastname'],
            $_POST['email'],
            $_POST['password'],
            $_POST['role'] ?? 'USER',
            $image
        ]);

        echo json_encode(['success' => true]);
    }

    // ===== EDIT USER =====
    elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        if (empty($id) || empty($_POST['username']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['email'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
            exit;
        }

        // Check duplicate email or username (exclude current user)
        $check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $check->execute([$_POST['email'], $_POST['username'], $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'อีเมลหรือชื่อผู้ใช้นี้ถูกใช้แล้วโดยผู้ใช้อื่น']);
            exit;
        }

        // Handle Image Update
        $image_sql = "";
        $image_params = [];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = uploadUserImage($_FILES['image']);
            $image_sql = ", image = ?";
            $image_params[] = $image;
        }

        // Update with or without password
        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ?, password = ?, role = ? $image_sql WHERE id = ?";
            $params = array_merge([
                $_POST['username'],
                $_POST['firstname'],
                $_POST['lastname'],
                $_POST['email'],
                $_POST['password'],
                $_POST['role'] ?? 'USER'
            ], $image_params, [$id]);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ?, role = ? $image_sql WHERE id = ?";
            $params = array_merge([
                $_POST['username'],
                $_POST['firstname'],
                $_POST['lastname'],
                $_POST['email'],
                $_POST['role'] ?? 'USER'
            ], $image_params, [$id]);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // Update session if editing the currently logged-in user
        if ($id == $_SESSION['user_id']) {
            $_SESSION['user_name'] = $_POST['firstname'] . ' ' . $_POST['lastname'];
            $_SESSION['user_username'] = $_POST['username'];
            $_SESSION['user_role'] = $_POST['role'] ?? $_SESSION['user_role'];
            if (!empty($image_params)) {
                $_SESSION['user_image'] = $image_params[0];
            }
        }

        echo json_encode(['success' => true]);
    }

    // ===== DELETE USER =====
    elseif ($action === 'delete') {
        if (empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ผู้ใช้']);
            exit;
        }

        // Prevent self-delete
        if ($_POST['id'] == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบบัญชีของตัวเองได้']);
            exit;
        }

        $pdo->beginTransaction();

        // Delete related borrowings for this user
        $pdo->prepare("DELETE FROM borrowings WHERE borrower_id = ?")->execute([$_POST['id']]);

        // Delete related stock imports created by this user
        $pdo->prepare(
            "DELETE sis FROM stock_import_serials sis
             JOIN stock_import_items sii ON sis.import_item_id = sii.id
             JOIN stock_imports si ON sii.import_id = si.id
             WHERE si.admin_id = ?"
        )->execute([$_POST['id']]);

        $pdo->prepare(
            "DELETE sii FROM stock_import_items sii
             JOIN stock_imports si ON sii.import_id = si.id
             WHERE si.admin_id = ?"
        )->execute([$_POST['id']]);

        $pdo->prepare("DELETE FROM stock_imports WHERE admin_id = ?")->execute([$_POST['id']]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['id']]);
        $pdo->commit();

        echo json_encode(['success' => true]);
    }

    else {
        echo json_encode(['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
