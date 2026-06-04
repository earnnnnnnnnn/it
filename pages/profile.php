<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$page_title = 'โปรไฟล์ของคุณ';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    
    // Check duplicate username/email
    $check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $check->execute([$email, $username, $_SESSION['user_id']]);
    if ($check->fetch()) {
        $error = 'อีเมลหรือชื่อผู้ใช้นี้ถูกใช้งานไปแล้ว';
    } else {
        // Update data
        $stmt = $pdo->prepare("UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$username, $firstname, $lastname, $email, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $firstname . ' ' . $lastname; // Update session
            $_SESSION['user_username'] = $username;
            $success = 'อัปเดตข้อมูลสำเร็จ';
            // Refresh local user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } else {
            $error = 'เกิดข้อผิดพลาด';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-6">
        <div class="card p-4 shadow-sm border-0">
            <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                    <img src="../assets/images/<?= $user['image'] ?>" class="rounded-circle border border-4 border-white shadow" width="120" height="120" alt="Profile">
                    <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" style="width: 32px; height: 32px;">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h5 class="fw-bold mt-3 mb-0"><?= $user['firstname'] . ' ' . $user['lastname'] ?></h5>
                <div class="text-muted small mb-2">@<?= htmlspecialchars($user['username']) ?></div>
                <span class="badge bg-primary-subtle text-primary px-3"><?= $user['role'] ?></span>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success small py-2"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username)</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">ชื่อ</label>
                        <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($user['firstname']) ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">นามสกุล</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($user['lastname']) ?>" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">อีเมล</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">บันทึกข้อมูล</button>
                    <button type="button" class="btn btn-light w-100 fw-bold">เปลี่ยนรหัสผ่าน</button>
                </div>
            </form>
        </div>
        
        <div class="card mt-4 p-4 shadow-sm border-0">
            <h6 class="fw-bold mb-3">สถิติการใช้งานของคุณ</h6>
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="p-3 bg-light rounded">
                        <?php 
                        $my_borrow = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE borrower_id = ?");
                        $my_borrow->execute([$_SESSION['user_id']]);
                        $count = $my_borrow->fetchColumn();
                        ?>
                        <div class="fs-4 fw-bold text-primary"><?= $count ?></div>
                        <div class="small text-muted">รายการที่เบิก</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded">
                        <div class="fs-4 fw-bold text-success"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                        <div class="small text-muted">วันที่เริ่มใช้งาน</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
