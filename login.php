<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Rate Limit Configuration
    $max_attempts = 5;
    $lockout_duration = 300; // 5 minutes

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    // Check if user is locked out
    if (isset($_SESSION['lockout_time'])) {
        if (time() < $_SESSION['lockout_time']) {
            $remaining = ceil(($_SESSION['lockout_time'] - time()) / 60);
            $error = "คุณพยายามเข้าสู่ระบบผิดพลาดหลายครั้งเกินไป กรุณารอ {$remaining} นาที";
            
            // If AJAX request, return immediately
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        } else {
            // Lockout expired, reset attempts
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['lockout_time']);
        }
    }

    // If not locked out (no error set yet), proceed with login check
    if (empty($error)) {
        // Verify reCAPTCHA
        $recaptcha_secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? "6Ld8NvEsAAAAAI2aaMTjkEPQdO6DEXgwnANSrGOs";
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        
        if (empty($recaptcha_response)) {
            $error = "กรุณายืนยันว่าคุณไม่ใช่โปรแกรมอัตโนมัติ (reCAPTCHA)";
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        } else {
            $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
            $verify_data = [
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response
            ];
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($verify_data)
                ]
            ];
            $context  = stream_context_create($options);
            $verify_result = @file_get_contents($verify_url, false, $context);
            $captcha_success = json_decode($verify_result);

            if (!$captcha_success || !$captcha_success->success) {
                $error = "การยืนยัน reCAPTCHA ไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        }

        if (empty($error)) {
            $identifier = $_POST['identifier'];
            $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_image'] = $user['image'];



        // Reset rate limit on successful login
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['lockout_time']);

        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: dashboard.php');
        exit;
    } else {
        // Failed login
        $_SESSION['login_attempts']++;
        
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['lockout_time'] = time() + $lockout_duration;
            $error = "คุณพยายามเข้าสู่ระบบผิดพลาดเกิน {$max_attempts} ครั้ง กรุณารอ 5 นาที";
        } else {
            $remaining_attempts = $max_attempts - $_SESSION['login_attempts'];
            $error = "ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง (เหลือโอกาสอีก {$remaining_attempts} ครั้ง)";
        }

        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IT Asset Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="bg-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="fas fa-boxes-stacked text-white fa-2x"></i>
            </div>
            <h4 class="fw-bold">AIMS Login</h4>
            <p class="text-muted small">Asset & Inventory Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">อีเมล หรือ ชื่อผู้ใช้</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="identifier" class="form-control bg-light border-start-0" placeholder="Username or Email" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            
            <div class="mb-4 d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="<?= $_ENV['RECAPTCHA_SITE_KEY'] ?? '6Ld8NvEsAAAAAGVFW4Gw-1wbTAL_bo8x0EnRJzxV' ?>"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">เข้าสู่ระบบ</button>
            <div class="text-center">
                <span class="small text-muted">ยังไม่มีบัญชี? </span>
                <a href="register.php" class="small text-primary text-decoration-none fw-bold">สมัครสมาชิก</a>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small">© 2026 IT Inventory System</p>
        </div>
    </div>
</body>
</html>
