<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    


    if (empty($error)) {
        if ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'อีเมลหรือชื่อผู้ใช้นี้ถูกใช้งานไปแล้ว';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?, 'USER')");
            if ($stmt->execute([$username, $firstname, $lastname, $email, $hashed_password])) {
                $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => true, 'message' => $success]);
                    exit;
                }
            } else {
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
            }
        }
            }
        }

    if ($error && isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - IT Asset Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body class="login-container">
    <div class="login-card" style="max-width: 450px;">
        <div class="text-center mb-4">
            <div class="bg-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="fas fa-user-plus text-white fa-2x"></i>
            </div>
            <h4 class="fw-bold">สมัครสมาชิกใหม่</h4>
            <p class="text-muted small">ร่วมเป็นส่วนหนึ่งของระบบจัดการครุภัณฑ์</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success small py-2"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-at text-muted"></i></span>
                    <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="username" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">ชื่อ</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="firstname" class="form-control bg-light border-start-0" placeholder="สมชาย" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-bold">นามสกุล</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="lastname" class="form-control bg-light border-start-0" placeholder="ใจดี" required>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">อีเมล</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="name@example.com" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">ยืนยันรหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                    <input type="password" name="confirm_password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                </div>
            </div>



            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">ลงชื่อเข้าใช้งาน</button>
            <div class="text-center">
                <span class="small text-muted">มีบัญชีอยู่แล้ว? </span>
                <a href="login.php" class="small text-primary text-decoration-none fw-bold">เข้าสู่ระบบ</a>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small">© 2026 IT Inventory System</p>
        </div>
    </div>
</body>
</html>
