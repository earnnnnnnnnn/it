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
                    <button type="button" class="btn btn-light w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fas fa-key me-1"></i>เปลี่ยนรหัสผ่าน
                    </button>
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

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="changePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="cpAlert" class="alert small py-2 d-none"></div>
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รหัสผ่านปัจจุบัน</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="current_password" id="cpCurrent" class="form-control bg-light border-start-0 border-end-0" placeholder="กรอกรหัสผ่านปัจจุบัน" required>
                            <button type="button" class="btn btn-light border border-start-0 toggle-pw" data-target="cpCurrent"><i class="fas fa-eye text-muted"></i></button>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รหัสผ่านใหม่</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="new_password" id="cpNew" class="form-control bg-light border-start-0 border-end-0" placeholder="ตั้งรหัสผ่านใหม่ (อย่างน้อย 4 ตัว)" required minlength="4">
                            <button type="button" class="btn btn-light border border-start-0 toggle-pw" data-target="cpNew"><i class="fas fa-eye text-muted"></i></button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">ยืนยันรหัสผ่านใหม่</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                            <input type="password" name="confirm_password" id="cpConfirm" class="form-control bg-light border-start-0 border-end-0" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required minlength="4">
                            <button type="button" class="btn btn-light border border-start-0 toggle-pw" data-target="cpConfirm"><i class="fas fa-eye text-muted"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnSubmitPassword">
                    <i class="fas fa-save me-1"></i>บันทึกรหัสผ่าน
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Submit password change
document.getElementById('btnSubmitPassword').addEventListener('click', function() {
    var form = document.getElementById('changePasswordForm');
    var alert = document.getElementById('cpAlert');
    var btn = this;

    var currentPw = document.getElementById('cpCurrent').value;
    var newPw = document.getElementById('cpNew').value;
    var confirmPw = document.getElementById('cpConfirm').value;

    // Client-side validation
    if (!currentPw || !newPw || !confirmPw) {
        alert.className = 'alert alert-danger small py-2';
        alert.textContent = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        alert.classList.remove('d-none');
        return;
    }
    if (newPw.length < 4) {
        alert.className = 'alert alert-danger small py-2';
        alert.textContent = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร';
        alert.classList.remove('d-none');
        return;
    }
    if (newPw !== confirmPw) {
        alert.className = 'alert alert-danger small py-2';
        alert.textContent = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
        alert.classList.remove('d-none');
        return;
    }

    // Disable button
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';
    alert.classList.add('d-none');

    var formData = new FormData(form);

    fetch('ajax_change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert.className = 'alert alert-danger small py-2';
            alert.textContent = res.message;
            alert.classList.remove('d-none');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>บันทึกรหัสผ่าน';
    })
    .catch(function() {
        alert.className = 'alert alert-danger small py-2';
        alert.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
        alert.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i>บันทึกรหัสผ่าน';
    });
});

// Reset form when modal closes
document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('changePasswordForm').reset();
    document.getElementById('cpAlert').classList.add('d-none');
    // Reset all password fields to type password
    document.querySelectorAll('#changePasswordModal input[type="text"]').forEach(function(input) {
        input.type = 'password';
    });
    document.querySelectorAll('#changePasswordModal .toggle-pw i').forEach(function(icon) {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
