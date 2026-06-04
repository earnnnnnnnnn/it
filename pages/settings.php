<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (($_SESSION['user_role'] ?? 'USER') !== 'SUPERADMIN') {
    header('Location: ../dashboard.php');
    exit;
}

$page_title = 'จัดการข้อมูลหลัก';

// Fetch data
$products = $pdo->query("SELECT p.*, 
    COUNT(ps.id) as serial_count,
    SUM(CASE WHEN ps.status = 'available' THEN 1 ELSE 0 END) as available_count
    FROM products p 
    LEFT JOIN product_serials ps ON p.id = ps.product_id 
    GROUP BY p.id 
    ORDER BY p.name ASC")->fetchAll();
$borrow_reasons = $pdo->query("SELECT * FROM reasons WHERE type = 'borrow' ORDER BY sort_order ASC, id ASC")->fetchAll();
$import_reasons = $pdo->query("SELECT * FROM reasons WHERE type = 'import' ORDER BY sort_order ASC, id ASC")->fetchAll();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$buildings = $pdo->query("SELECT * FROM buildings ORDER BY sort_order ASC, id ASC")->fetchAll();
$floors = $pdo->query("SELECT * FROM floors ORDER BY sort_order ASC, id ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY sort_order ASC, id ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
$units = $pdo->query("SELECT * FROM units ORDER BY sort_order ASC, id ASC")->fetchAll();

$allowed_tabs = ['buildings', 'floors', 'departments', 'products', 'users', 'borrow', 'import', 'categories', 'units'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs, true) ? $_GET['tab'] : 'buildings';

require_once '../includes/header.php';
?>

<!-- Mobile Sub-navigation for settings tabs (Visible only on mobile/tablets) -->
<div class="d-lg-none mb-4" style="overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch;">
    <div class="d-inline-flex gap-2 p-1 bg-white border rounded-pill shadow-sm">
        <a href="?tab=buildings" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'buildings' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-building me-1"></i> อาคาร
        </a>
        <a href="?tab=floors" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'floors' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-layer-group me-1"></i> ชั้น
        </a>
        <a href="?tab=departments" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'departments' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-network-wired me-1"></i> แผนก
        </a>
        <a href="?tab=products" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'products' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-boxes me-1"></i> สินค้า
        </a>
        <a href="?tab=users" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'users' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-users me-1"></i> ผู้ใช้งาน
        </a>
        <a href="?tab=borrow" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'borrow' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-hand-holding-heart me-1"></i> เหตุผลการเบิก
        </a>
        <a href="?tab=import" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'import' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-file-import me-1"></i> เหตุผลการนำเข้า
        </a>
        <a href="?tab=categories" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'categories' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-tags me-1"></i> หมวดหมู่
        </a>
        <a href="?tab=units" class="btn btn-sm rounded-pill px-3 py-1.5 <?= $active_tab == 'units' ? 'btn-primary' : 'btn-light text-muted' ?>">
            <i class="fas fa-balance-scale me-1"></i> หน่วยนับ
        </a>
    </div>
</div>

<!-- Desktop Sub-navigation for settings tabs (Visible only on large screens) -->
<div class="d-none d-lg-flex align-items-center justify-content-between gap-2 mb-4">
    <a href="?tab=buildings" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'buildings' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-building me-1"></i> อาคาร
    </a>
    <a href="?tab=floors" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'floors' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-layer-group me-1"></i> ชั้น
    </a>
    <a href="?tab=departments" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'departments' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-network-wired me-1"></i> แผนก
    </a>
    <a href="?tab=products" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'products' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-boxes me-1"></i> สินค้า
    </a>
    <a href="?tab=users" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'users' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-users me-1"></i> ผู้ใช้งาน
    </a>
    <a href="?tab=borrow" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'borrow' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-hand-holding-heart me-1"></i> เหตุผลการเบิก
    </a>
    <a href="?tab=import" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'import' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-file-import me-1"></i> เหตุผลการนำเข้า
    </a>
    <a href="?tab=categories" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'categories' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-tags me-1"></i> หมวดหมู่
    </a>
    <a href="?tab=units" class="btn btn-sm rounded-pill px-3 py-2 <?= $active_tab == 'units' ? 'btn-primary' : 'btn-light text-muted' ?>">
        <i class="fas fa-balance-scale me-1"></i> หน่วยนับ
    </a>
</div>

<div class="tab-content">

    <!-- ====== TAB 1: Products ====== -->
    <div class="tab-pane fade <?= $active_tab == 'products' ? 'show active' : '' ?>" id="tabProducts">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="fas fa-boxes text-primary me-2"></i>รายการสินค้าทั้งหมด</h6>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหาสินค้า..." onkeyup="filterTable(this, 'tabProducts')">
                    </div>
                    <button class="btn btn-primary btn-sm fw-bold" onclick="addProduct()">
                        <i class="fas fa-plus-circle me-1"></i> เพิ่มสินค้าใหม่
                    </button>
                    <span class="badge bg-primary rounded-pill"><?= count($products) ?> รายการ</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>สินค้า</th>
                            <th>SKU</th>
                            <th>หมวดหมู่</th>
                            <th class="text-center">ขั้นต่ำ</th>
                            <th class="text-center">คงเหลือ</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): $min = $p["min_alert"] ?? 0; $avail = $p["available_count"] ?? 0; $needed = max(0, $min - $avail); ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/<?= $p['image'] ?>" class="rounded border" width="40" height="40" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($p['brand'] ?? '') ?> <?= htmlspecialchars($p['model'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['category'] ?? '-') ?></span></td>
                            <td class="text-center fw-bold text-muted"><?= $min ?></td>
                            <td class="text-center">
                                <div class="fw-bold text-primary fs-5"><?= $avail ?></div>
                                <button class="btn btn-sm btn-link text-decoration-none p-0" style="font-size: 10px;" onclick="viewSerials(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-eye me-1"></i>ดูรายละเอียด
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if ($needed > 0): ?>
                                    <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse">
                                        <i class="fas fa-exclamation-triangle me-1"></i>ต้องเบิก
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>พร้อมใช้
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">ยังไม่มีสินค้าในระบบ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====== TAB 2: Borrow Reasons ====== -->
    <div class="tab-pane fade <?= $active_tab == 'borrow' ? 'show active' : '' ?>" id="tabBorrowReasons">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มเหตุผลการเบิก</h6>
                    <div class="input-group">
                        <input type="text" id="newBorrowReason" class="form-control" placeholder="พิมพ์เหตุผลใหม่...">
                        <button class="btn btn-primary" onclick="addReason('borrow')"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-list text-primary me-2"></i>รายการเหตุผลการเบิก</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'borrowReasonList')">
                        </div>
                    </div>
                    <div id="borrowReasonList" class="reorder-list">
                        <?php foreach ($borrow_reasons as $r): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="reason-<?= $r['id'] ?>" data-id="<?= $r['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($r['label']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editReason(<?= $r['id'] ?>, '<?= htmlspecialchars($r['label'], ENT_QUOTES) ?>', 'borrow')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteReason(<?= $r['id'] ?>, '<?= htmlspecialchars($r['label'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($borrow_reasons)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีเหตุผลการเบิก</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 3: Import Reasons ====== -->
    <div class="tab-pane fade <?= $active_tab == 'import' ? 'show active' : '' ?>" id="tabImportReasons">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มเหตุผลการนำเข้า</h6>
                    <div class="input-group">
                        <input type="text" id="newImportReason" class="form-control" placeholder="พิมพ์เหตุผลใหม่...">
                        <button class="btn btn-primary" onclick="addReason('import')"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-list text-primary me-2"></i>รายการเหตุผลการนำเข้า</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'importReasonList')">
                        </div>
                    </div>
                    <div id="importReasonList" class="reorder-list">
                        <?php foreach ($import_reasons as $r): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="reason-<?= $r['id'] ?>" data-id="<?= $r['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($r['label']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editReason(<?= $r['id'] ?>, '<?= htmlspecialchars($r['label'], ENT_QUOTES) ?>', 'import')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteReason(<?= $r['id'] ?>, '<?= htmlspecialchars($r['label'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($import_reasons)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีเหตุผลการนำเข้า</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 4: User Management ====== -->
    <div class="tab-pane fade <?= $active_tab == 'users' ? 'show active' : '' ?>" id="tabUsers">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm border-0">
                    <form id="addUserForm" enctype="multipart/form-data">
                        <div class="mb-4 text-center">
                            <div class="position-relative d-inline-block">
                                <img id="addImgPreview" src="https://api.dicebear.com/9.x/adventurer-neutral/svg?seed=NewUser&backgroundColor=ecfdf5" class="rounded-circle border shadow-sm" width="100" height="100" style="object-fit: cover; background-color: #ecfdf5;">
                                <label for="addUserImage" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="image" id="addUserImage" class="d-none" accept="image/*" onchange="previewImg(this, 'addImgPreview')">
                            </div>
                            <div class="small text-muted mt-2">รูปโปรไฟล์</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required placeholder="username">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" name="firstname" class="form-control" required placeholder="ชื่อ">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="lastname" class="form-control" required placeholder="นามสกุล">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="example@email.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="ตั้งรหัสผ่าน">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">สิทธิ์การใช้งาน</label>
                            <select name="role" class="form-select">
                                <option value="USER">USER (เบิก/คืน)</option>
                                <option value="ADMIN">ADMIN (จัดการทั้งหมด)</option>
                                <option value="SUPERADMIN">SUPERADMIN (ผู้ดูแลระบบสูงสุด)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                            <i class="fas fa-save me-2"></i>บันทึกผู้ใช้
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <h6 class="fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i>รายชื่อผู้ใช้ทั้งหมด</h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 200px;">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหาผู้ใช้..." onkeyup="filterTable(this, 'tabUsers')">
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= count($users) ?> คน</span>
                        </div>
                    </div>
                    <ul class="nav nav-pills mb-3" id="userTypeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-users-tab" data-bs-toggle="pill" data-bs-target="#general-users" type="button" role="tab"><i class="fas fa-user text-info me-1"></i> ผู้ใช้งานทั่วไป</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admins-tab" data-bs-toggle="pill" data-bs-target="#admins" type="button" role="tab"><i class="fas fa-user-shield text-danger me-1"></i> ผู้ดูแลระบบ</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="superadmins-tab" data-bs-toggle="pill" data-bs-target="#superadmins" type="button" role="tab"><i class="fas fa-crown text-warning me-1"></i> ผู้ดูแลระบบสูงสุด</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="userTypeTabsContent">
                        <!-- General Users Tab -->
                        <div class="tab-pane fade show active" id="general-users" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ผู้ใช้</th>
                                            <th>อีเมล</th>
                                            <th>สิทธิ์</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): if ($u['role'] == 'ADMIN' || $u['role'] == 'SUPERADMIN') continue; ?>
                                        <tr id="user-row-<?= $u['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= !empty($u['image']) && $u['image'] !== 'default_user.png' ? '../assets/images/' . $u['image'] : 'https://api.dicebear.com/9.x/adventurer-neutral/svg?seed=' . urlencode($u['username'] ?? 'User') . '&backgroundColor=ecfdf5' ?>" class="rounded-circle border" width="35" height="35" style="object-fit: cover; background-color: #ecfdf5;">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></div>
                                                        <div class="text-muted small">@<?= htmlspecialchars($u['username']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">ผู้ใช้ทั่วไป</span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['firstname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['lastname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', '<?= $u['role'] ?>', '<?= $u['image'] ?>')" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname'], ENT_QUOTES) ?>')" title="ลบ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Admins Tab -->
                        <div class="tab-pane fade" id="admins" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ผู้ใช้</th>
                                            <th>อีเมล</th>
                                            <th>สิทธิ์</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): if ($u['role'] != 'ADMIN') continue; ?>
                                        <tr id="user-row-<?= $u['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= !empty($u['image']) && $u['image'] !== 'default_user.png' ? '../assets/images/' . $u['image'] : 'https://api.dicebear.com/9.x/adventurer-neutral/svg?seed=' . urlencode($u['username'] ?? 'User') . '&backgroundColor=ecfdf5' ?>" class="rounded-circle border" width="35" height="35" style="object-fit: cover; background-color: #ecfdf5;">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></div>
                                                        <div class="text-muted small">@<?= htmlspecialchars($u['username']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span class="badge bg-danger-subtle text-danger">ผู้ดูแลระบบ</span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['firstname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['lastname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', '<?= $u['role'] ?>', '<?= $u['image'] ?>')" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname'], ENT_QUOTES) ?>')" title="ลบ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Superadmins Tab -->
                        <div class="tab-pane fade" id="superadmins" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ผู้ใช้</th>
                                            <th>อีเมล</th>
                                            <th>สิทธิ์</th>
                                            <th class="text-end">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): if ($u['role'] != 'SUPERADMIN') continue; ?>
                                        <tr id="user-row-<?= $u['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= !empty($u['image']) && $u['image'] !== 'default_user.png' ? '../assets/images/' . $u['image'] : 'https://api.dicebear.com/9.x/adventurer-neutral/svg?seed=' . urlencode($u['username'] ?? 'User') . '&backgroundColor=ecfdf5' ?>" class="rounded-circle border" width="35" height="35" style="object-fit: cover; background-color: #ecfdf5;">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></div>
                                                        <div class="text-muted small">@<?= htmlspecialchars($u['username']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning"><i class="fas fa-crown me-1"></i>ผู้ดูแลระบบสูงสุด</span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['firstname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['lastname'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', '<?= $u['role'] ?>', '<?= $u['image'] ?>')" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname'], ENT_QUOTES) ?>')" title="ลบ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 5: Manage Buildings ====== -->
    <div class="tab-pane fade <?= $active_tab == 'buildings' ? 'show active' : '' ?>" id="tabBuildings">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มอาคารใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newBuildingName" class="form-control" placeholder="พิมพ์ชื่ออาคารใหม่...">
                        <button class="btn btn-primary fw-bold animate-hover" onclick="addBuilding()"><i class="fas fa-plus me-1"></i>เพิ่ม</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-building text-primary me-2"></i>รายการอาคารทั้งหมด</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'buildingList')">
                        </div>
                    </div>
                    <div id="buildingList" class="reorder-list">
                        <?php foreach ($buildings as $b): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="building-<?= $b['id'] ?>" data-id="<?= $b['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($b['name']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editBuilding(<?= $b['id'] ?>, '<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteBuilding(<?= $b['id'] ?>, '<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($buildings)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีอาคารในระบบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 6: Manage Floors ====== -->
    <div class="tab-pane fade <?= $active_tab == 'floors' ? 'show active' : '' ?>" id="tabFloors">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มชั้นใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newFloorName" class="form-control" placeholder="พิมพ์ชื่อชั้นใหม่...">
                        <button class="btn btn-primary fw-bold animate-hover" onclick="addFloor()"><i class="fas fa-plus me-1"></i>เพิ่ม</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-layer-group text-primary me-2"></i>รายการชั้นทั้งหมด</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'floorList')">
                        </div>
                    </div>
                    <div id="floorList" class="reorder-list">
                        <?php foreach ($floors as $f): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="floor-<?= $f['id'] ?>" data-id="<?= $f['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($f['name']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editFloor(<?= $f['id'] ?>, '<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteFloor(<?= $f['id'] ?>, '<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($floors)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีชั้นในระบบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 7: Manage Departments ====== -->
    <div class="tab-pane fade <?= $active_tab == 'departments' ? 'show active' : '' ?>" id="tabDepartments">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มแผนกใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newDepartmentName" class="form-control" placeholder="พิมพ์ชื่อแผนกใหม่...">
                        <button class="btn btn-primary fw-bold animate-hover" onclick="addDepartment()"><i class="fas fa-plus me-1"></i>เพิ่ม</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-network-wired text-primary me-2"></i>รายการแผนกทั้งหมด</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'departmentList')">
                        </div>
                    </div>
                    <div id="departmentList" class="reorder-list">
                        <?php foreach ($departments as $d): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="department-<?= $d['id'] ?>" data-id="<?= $d['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($d['name']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editDepartment(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($departments)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีแผนกในระบบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ====== TAB 8: Manage Categories ====== -->
    <div class="tab-pane fade <?= $active_tab == 'categories' ? 'show active' : '' ?>" id="tabCategories">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มหมวดหมู่ใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newCategoryName" class="form-control" placeholder="พิมพ์ชื่อหมวดหมู่ใหม่...">
                        <button class="btn btn-primary fw-bold animate-hover" onclick="addCategory()"><i class="fas fa-plus me-1"></i>เพิ่ม</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-tags text-primary me-2"></i>รายการหมวดหมู่ทั้งหมด</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'categoryList')">
                        </div>
                    </div>
                    <div id="categoryList" class="reorder-list">
                        <?php foreach ($categories as $c): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="category-<?= $c['id'] ?>" data-id="<?= $c['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($c['name']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีหมวดหมู่ในระบบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== TAB 9: Manage Units ====== -->
    <div class="tab-pane fade <?= $active_tab == 'units' ? 'show active' : '' ?>" id="tabUnits">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0">
                    <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>เพิ่มหน่วยนับใหม่</h6>
                    <div class="input-group">
                        <input type="text" id="newUnitName" class="form-control" placeholder="พิมพ์ชื่อหน่วยนับใหม่...">
                        <button class="btn btn-primary fw-bold animate-hover" onclick="addUnit()"><i class="fas fa-plus me-1"></i>เพิ่ม</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-balance-scale text-primary me-2"></i>รายการหน่วยนับทั้งหมด</h6>
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" placeholder="ค้นหา..." onkeyup="filterList(this, 'unitList')">
                        </div>
                    </div>
                    <div id="unitList" class="reorder-list">
                        <?php foreach ($units as $u): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="unit-<?= $u['id'] ?>" data-id="<?= $u['id'] ?>" style="cursor: grab;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                            
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUnit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUnit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($units)): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีหน่วยนับในระบบ</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-box me-2"></i><span id="productModalTitle">แก้ไขสินค้า</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="epId" name="id">
                <div class="row g-3">
                    <!-- Left: Product Info -->
                    <div class="col-lg-7">
                        <div class="card p-3 border-0 shadow-sm h-100">
                            <h6 class="fw-bold mb-3 small"><i class="fas fa-info-circle text-primary me-2"></i>ข้อมูลหลักสินค้า</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Barcode หลัก (SKU) <span class="text-danger">*</span></label>
                                    <input type="text" id="epSku" class="form-control form-control-sm scan-focus border-orange bg-orange-light" placeholder="เช่น SKU-001" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                                    <input type="text" id="epName" class="form-control form-control-sm" placeholder="เช่น Logitech G Pro" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select id="epCategory" class="form-select form-select-sm" required>
                                        <option value="">-- เลือกหมวดหมู่ --</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ยี่ห้อ <span class="text-danger">*</span></label>
                                    <input type="text" id="epBrand" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">รุ่น <span class="text-danger">*</span></label>
                                    <input type="text" id="epModel" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">สเปก / รายละเอียด</label>
                                    <textarea id="epSpec" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ราคาต่อหน่วย <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" id="epPrice" class="form-control form-control-sm" value="0.00" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">หน่วยนับ <span class="text-danger">*</span></label>
                                    <select id="epUnit" class="form-select form-select-sm" required>
                                        <option value="">-- เลือกหน่วยนับ --</option>
                                        <?php foreach ($units as $u): ?>
                                            <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">แจ้งเตือนขั้นต่ำ <span class="text-danger">*</span></label>
                                    <input type="number" id="epMinAlert" class="form-control form-control-sm" value="5" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">รูปสินค้า <span class="text-danger">*</span></label>
                                    <input type="file" id="epImage" class="form-control form-control-sm" accept="image/*" onchange="previewImg(this, 'epImagePreview')">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div id="epImagePreview" class="border rounded bg-white d-flex align-items-center justify-content-center" style="width: 100%; height: 31px; overflow: hidden;">
                                        <span class="text-muted" style="font-size: 10px;">ไม่มีรูป</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Serials (Only show when adding) -->
                    <div class="col-lg-5" id="epSerialCol">
                        <div class="card p-3 border-0 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 small"><i class="fas fa-barcode text-primary me-2"></i>ยิง Serial / Barcode แยกชิ้น</h6>
                                <span class="badge bg-primary rounded-pill" id="epSerialCount">0</span>
                            </div>
                            
                            <div class="mb-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-orange text-white border-orange"><i class="fas fa-qrcode"></i></span>
                                    <input type="text" id="epSerialInput" class="form-control border-orange bg-orange-light fw-bold" placeholder="สแกน Barcode ตรงนี้...">
                                </div>
                            </div>

                            <div id="epSerialList" class="overflow-auto" style="max-height: 300px;">
                                <div class="text-center text-muted py-4" id="epEmptySerial">
                                    <i class="fas fa-barcode fa-2x mb-2 opacity-25"></i>
                                    <p style="font-size: 11px;">ยังไม่มีข้อมูล Serial</p>
                                </div>
                                <div id="epSerialItems"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="submitEditProduct()"><i class="fas fa-save me-1"></i>บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- View Serials Modal -->
<div class="modal fade" id="viewSerialsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-barcode me-2"></i>รายการ Serial: <span id="vsProductName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Serial Number</th>
                                <th>เลขครุภัณฑ์</th>
                                <th>อาคาร / สถานที่</th>
                                <th>สถานะ</th>
                                <th>รายละเอียดการเบิก</th>
                            </tr>
                        </thead>
                        <tbody id="serialListBody">
                            <!-- Serials will be loaded here -->
                        </tbody>
                    </table>
                </div>
                <div id="vsLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>แก้ไขข้อมูลผู้ใช้</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-4 text-center">
                        <div class="position-relative d-inline-block">
                            <img id="editImgPreview" src="../assets/images/default_user.png" class="rounded-circle border shadow-sm" width="100" height="100" style="object-fit: cover;">
                            <label for="editUserImage" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" name="image" id="editUserImage" class="d-none" accept="image/*" onchange="previewImg(this, 'editImgPreview')">
                        </div>
                        <div class="small text-muted mt-2">รูปโปรไฟล์</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" id="editUsername" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">ชื่อ</label>
                            <input type="text" id="editFirstname" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">นามสกุล</label>
                            <input type="text" id="editLastname" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">อีเมล</label>
                        <input type="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">รหัสผ่านใหม่ <span class="text-muted">(เว้นว่างถ้าไม่เปลี่ยน)</span></label>
                        <input type="password" id="editPassword" class="form-control" placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยน">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">สิทธิ์การใช้งาน</label>
                        <select id="editRole" class="form-select">
                            <option value="USER">USER (เบิก/คืน)</option>
                            <option value="ADMIN">ADMIN (จัดการทั้งหมด)</option>
                            <option value="SUPERADMIN">SUPERADMIN (ผู้ดูแลระบบสูงสุด)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="submitEditUser()">
                    <i class="fas fa-save me-1"></i>บันทึก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== SEARCH FUNCTIONS =====
    function filterTable(input, tabId) {
        var filter = input.value.toLowerCase();
        var rows = document.querySelectorAll('#' + tabId + ' tbody tr');
        rows.forEach(function(row) {
            if (row.children.length === 1 && row.children[0].classList.contains('text-center')) {
                return;
            }
            var text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    function filterList(input, listId) {
        var filter = input.value.toLowerCase();
        var items = document.querySelectorAll('#' + listId + ' > .reason-item');
        items.forEach(function(item) {
            var text = item.innerText.toLowerCase();
            item.style.setProperty('display', text.includes(filter) ? 'flex' : 'none', 'important');
        });
    }

    // ===== PRODUCT MANAGEMENT =====
    const epSerials = new Set();

    function updateEpSerialList() {
        $('#epSerialItems').empty();
        if (epSerials.size === 0) {
            $('#epEmptySerial').removeClass('d-none');
            $('#epSerialCount').text(0);
            return;
        }
        $('#epEmptySerial').addClass('d-none');
        let index = epSerials.size;
        epSerials.forEach(code => {
            $('#epSerialItems').prepend(`
                <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded mb-2 border shadow-sm small animate-fade-in">
                    <div class="fw-bold text-dark">${code}</div>
                    <button type="button" class="btn btn-link text-danger p-0 remove-ep-serial" data-code="${code}" style="text-decoration: none;">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </div>
            `);
        });
        $('#epSerialCount').text(epSerials.size);
    }

    $(document).on('click', '.remove-ep-serial', function() {
        epSerials.delete($(this).data('code'));
        updateEpSerialList();
    });

    $('#epSerialInput').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const code = $(this).val().trim();
            if (code.length > 2) {
                if (!epSerials.has(code)) {
                    epSerials.add(code);
                    updateEpSerialList();
                    $(this).val('').focus();
                } else {
                    Swal.fire({ icon: 'warning', title: 'Serial ซ้ำ', timer: 1000, showConfirmButton: false });
                }
            }
        }
    });

    function addProduct() {
        document.getElementById('productModalTitle').innerText = 'เพิ่มสินค้าใหม่';
        document.getElementById('epId').value = '';
        document.getElementById('epName').value = '';
        document.getElementById('epSku').value = '';
        document.getElementById('epBrand').value = '';
        document.getElementById('epModel').value = '';
        document.getElementById('epCategory').value = 'IT';
        document.getElementById('epSpec').value = '';
        document.getElementById('epPrice').value = '0.00';
        document.getElementById('epUnit').value = 'ชิ้น';
        document.getElementById('epMinAlert').value = 5;
        document.getElementById('epImagePreview').innerHTML = '<span class="text-muted" style="font-size: 10px;">ไม่มีรูป</span>';
        document.getElementById('epImage').value = '';
        document.getElementById('epImage').required = true;
        
        epSerials.clear();
        updateEpSerialList();
        document.getElementById('epSerialCol').style.display = 'block';

        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    }

    function editProduct(p) {
        document.getElementById('productModalTitle').innerText = 'แก้ไขสินค้า';
        document.getElementById('epId').value = p.id;
        document.getElementById('epName').value = p.name || '';
        document.getElementById('epSku').value = p.sku || '';
        document.getElementById('epBrand').value = p.brand || '';
        document.getElementById('epModel').value = p.model || '';
        document.getElementById('epCategory').value = p.category || 'IT';
        document.getElementById('epSpec').value = p.spec || '';
        document.getElementById('epPrice').value = p.price || '0.00';
        document.getElementById('epUnit').value = p.unit || 'ชิ้น';
        document.getElementById('epMinAlert').value = p.min_alert || 0;
        
        document.getElementById('epSerialCol').style.display = 'none';
        
        // Image preview
        var preview = document.getElementById('epImagePreview');
        if (p.image) {
            preview.innerHTML = `<img src="../assets/images/${p.image}" style="height: 100%; width: auto;" onerror="this.src='../assets/images/default_product.png'">`;
        } else {
            preview.innerHTML = '<span class="text-muted small">ไม่มีรูป</span>';
        }
        document.getElementById('epImage').value = ''; // Reset file input
        document.getElementById('epImage').required = false;

        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    }

    function submitEditProduct() {
        var formData = new FormData();
        var id = document.getElementById('epId').value;
        formData.append('action', id ? 'edit_product' : 'add_product');
        formData.append('id', id);
        formData.append('name', document.getElementById('epName').value.trim());
        formData.append('sku', document.getElementById('epSku').value.trim());
        formData.append('brand', document.getElementById('epBrand').value.trim());
        formData.append('model', document.getElementById('epModel').value.trim());
        formData.append('category', document.getElementById('epCategory').value.trim());
        formData.append('spec', document.getElementById('epSpec').value.trim());
        formData.append('price', document.getElementById('epPrice').value);
        formData.append('unit', document.getElementById('epUnit').value.trim());
        formData.append('min_alert', document.getElementById('epMinAlert').value);
        
        if (epSerials.size > 0) {
            formData.append('serials', Array.from(epSerials).join(','));
        }
        
        var imageInput = document.getElementById('epImage');
        if (!id && imageInput.files.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ข้อมูลไม่ครบ', text: 'กรุณาอัปโหลดรูปภาพสินค้า' });
            return;
        }

        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }
        
        fetchAction(formData);
    }

    function deleteProduct(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ลบสินค้า <strong>' + name + '</strong> และ Serial ทั้งหมดที่เกี่ยวข้อง?<br><span class="text-danger small">การลบจะไม่สามารถกู้คืนได้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_product', id: id });
            }
        });
    }

    // ===== REASON MANAGEMENT =====
    function addReason(type) {
        var inputId = type === 'borrow' ? 'newBorrowReason' : 'newImportReason';
        var label = document.getElementById(inputId).value.trim();
        if (!label) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกเหตุผล' });
            return;
        }
        fetchAction({ action: 'add_reason', type: type, label: label });
    }

    function editReason(id, oldLabel, type) {
        Swal.fire({
            title: 'แก้ไขเหตุผล',
            input: 'text',
            inputValue: oldLabel,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกเหตุผล'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_reason', id: id, label: result.value });
            }
        });
    }

    // ===== SERIAL MANAGEMENT =====
    function viewSerials(productId, productName) {
        document.getElementById('vsProductName').innerText = productName;
        var body = document.getElementById('serialListBody');
        var loading = document.getElementById('vsLoading');
        
        body.innerHTML = '';
        loading.classList.remove('d-none');
        
        var modal = new bootstrap.Modal(document.getElementById('viewSerialsModal'));
        modal.show();

        fetch('ajax_manage_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_serials', product_id: productId })
        })
        .then(r => r.json())
        .then(res => {
            loading.classList.add('d-none');
            if (res.success) {
                if (res.serials.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูล Serial</td></tr>';
                    return;
                }
                
                res.serials.forEach(s => {
                    var statusBadge = s.status === 'available' 
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fas fa-check-circle me-1"></i>ในสต็อก</span>'
                        : '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fas fa-hand-holding me-1"></i>เบิกออก</span>';
                    
                    var location = '-';
                    if (s.building || s.floor || s.department) {
                        let locParts = [];
                        if (s.building) locParts.push(s.building);
                        if (s.floor) locParts.push(s.floor);
                        if (s.department) locParts.push(s.department);
                        location = `<span class="badge bg-light text-dark border small"><i class="fas fa-building text-muted me-1"></i>${locParts.join(' / ')}</span>`;
                    }

                    var details = '-';
                    if (s.status === 'borrowed') {
                        var bName = s.borrower || 'ไม่ระบุผู้เบิก';
                        var bTime = s.borrowed_at || '-';
                        details = `<div class="small fw-bold text-dark">${bName}</div>
                                   <div class="small text-muted">${bTime}</div>`;
                    }

                    body.innerHTML += `
                        <tr>
                            <td class="ps-4"><code>${s.serial_code}</code></td>
                            <td><span class="text-muted small">${s.asset_number || '-'}</span></td>
                            <td>${location}</td>
                            <td>${statusBadge}</td>
                            <td>${details}</td>
                        </tr>
                    `;
                });
            } else {
                body.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">${res.message}</td></tr>`;
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            body.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
            console.error(err);
        });
    }

    function deleteReason(id, label) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ลบเหตุผล <strong>' + label + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_reason', id: id });
            }
        });
    }

    // ===== BUILDING MANAGEMENT =====
    function addBuilding() {
        var name = document.getElementById('newBuildingName').value.trim();
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่ออาคาร' });
            return;
        }
        fetchAction({ action: 'add_building', name: name });
    }

    function editBuilding(id, oldName) {
        Swal.fire({
            title: 'แก้ไขชื่ออาคาร',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกชื่ออาคาร'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_building', id: id, name: result.value });
            }
        });
    }

    function deleteBuilding(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบอาคาร <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_building', id: id });
            }
        });
    }

    // ===== FLOOR MANAGEMENT =====
    function addFloor() {
        var name = document.getElementById('newFloorName').value.trim();
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อชั้น' });
            return;
        }
        fetchAction({ action: 'add_floor', name: name });
    }

    function editFloor(id, oldName) {
        Swal.fire({
            title: 'แก้ไขชื่อชั้น',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกชื่อชั้น'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_floor', id: id, name: result.value });
            }
        });
    }

    function deleteFloor(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบชั้น <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_floor', id: id });
            }
        });
    }

    // ===== DEPARTMENT MANAGEMENT =====
    function addDepartment() {
        var name = document.getElementById('newDepartmentName').value.trim();
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อแผนก' });
            return;
        }
        fetchAction({ action: 'add_department', name: name });
    }

    // Edit department function
    function editDepartment(id, oldName) {
        Swal.fire({
            title: 'แก้ไขชื่อแผนก',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกชื่อแผนก'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_department', id: id, name: result.value });
            }
        });
    }

    function deleteDepartment(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบแผนก <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_department', id: id });
            }
        });
    }

    // ===== CATEGORY MANAGEMENT =====
    function addCategory() {
        var name = document.getElementById('newCategoryName').value.trim();
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อหมวดหมู่' });
            return;
        }
        fetchAction({ action: 'add_category', name: name });
    }

    function editCategory(id, oldName) {
        Swal.fire({
            title: 'แก้ไขชื่อหมวดหมู่',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกชื่อหมวดหมู่'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_category', id: id, name: result.value });
            }
        });
    }

    function deleteCategory(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบหมวดหมู่ <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_category', id: id });
            }
        });
    }

    // ===== UNIT MANAGEMENT =====
    function addUnit() {
        var name = document.getElementById('newUnitName').value.trim();
        if (!name) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อหน่วยนับ' });
            return;
        }
        fetchAction({ action: 'add_unit', name: name });
    }

    function editUnit(id, oldName) {
        Swal.fire({
            title: 'แก้ไขชื่อหน่วยนับ',
            input: 'text',
            inputValue: oldName,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: function(value) { if (!value) return 'กรุณากรอกชื่อหน่วยนับ'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'edit_unit', id: id, name: result.value });
            }
        });
    }

    function deleteUnit(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบหน่วยนับ <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetchAction({ action: 'delete_unit', id: id });
            }
        });
    }

    // ===== SHARED FETCH =====
    function fetchAction(data) {
        var options = {
            method: 'POST'
        };
        
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers = { 'Content-Type': 'application/json' };
            options.body = JSON.stringify(data);
        }

        fetch('ajax_manage_settings.php', {
            method: 'POST',
            headers: data instanceof FormData ? {} : { 'Content-Type': 'application/json' },
            body: data instanceof FormData ? data : JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', timer: 1000, showConfirmButton: false }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
            }
        });
    }

    // Image preview for edit modal
    document.getElementById('epImage').addEventListener('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('epImagePreview').innerHTML = `<img src="${e.target.result}" style="height: 100%; width: auto;">`;
            }
            reader.readAsDataURL(file);
        }
    });

    // ===== USER MANAGEMENT =====
    function previewImg(input, targetId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(targetId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'add');
        fetchUserAction(formData);
    });

    function editUser(id, username, firstname, lastname, email, role, image) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editUsername').value = username;
        document.getElementById('editFirstname').value = firstname;
        document.getElementById('editLastname').value = lastname;
        document.getElementById('editEmail').value = email;
        document.getElementById('editPassword').value = '';
        document.getElementById('editRole').value = role;
        document.getElementById('editImgPreview').src = '../assets/images/' + (image || 'default_user.png');
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }

    function submitEditUser() {
        var form = document.getElementById('editUserForm');
        var formData = new FormData(form);
        formData.append('action', 'edit');
        formData.append('username', document.getElementById('editUsername').value.trim());
        formData.append('firstname', document.getElementById('editFirstname').value.trim());
        formData.append('lastname', document.getElementById('editLastname').value.trim());
        formData.append('email', document.getElementById('editEmail').value.trim());
        formData.append('password', document.getElementById('editPassword').value);
        formData.append('role', document.getElementById('editRole').value);
        fetchUserAction(formData);
    }

    function deleteUser(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: 'ต้องการลบผู้ใช้ <strong>' + name + '</strong> ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                fetchUserAction(formData);
            }
        });
    }

    function fetchUserAction(formData) {
        fetch('ajax_manage_user.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', timer: 1000, showConfirmButton: false }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
            }
        });
    }

    $(document).ready(function() {

        // Initialize Sortable for Reasons and Buildings
        const initSortable = (id, saveUrl) => {
            const el = document.getElementById(id);
            if (el) {
                new Sortable(el, {
                    animation: 150,
                    onEnd: function() {
                        const order = [];
                        $("#" + id + " .reason-item").each(function() {
                            order.push($(this).data("id"));
                        });
                        saveOrder(order, saveUrl);
                    }
                });
            }
        };

        const saveOrder = (order, saveUrl) => {
            $.ajax({
                url: saveUrl,
                method: "POST",
                data: JSON.stringify({ order: order }),
                contentType: "application/json",
                success: function(res) {
                    const result = JSON.parse(res);
                    if (!result.success) {
                        Swal.fire({ icon: "error", title: "ไม่สามารถบันทึกลำดับได้", text: result.message });
                    }
                }
            });
        };

        initSortable("borrowReasonList", "ajax_reorder_reasons.php");
        initSortable("importReasonList", "ajax_reorder_reasons.php");
        initSortable("buildingList", "ajax_reorder_buildings.php");
        initSortable("floorList", "ajax_reorder_floors.php");
        initSortable("departmentList", "ajax_reorder_departments.php");
        initSortable("categoryList", "ajax_reorder_categories.php");
        initSortable("unitList", "ajax_reorder_units.php");

        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get("tab");
        if (tab) {
            let target = "";
            if (tab === "products") target = "#tabProducts";
            else if (tab === "users") target = "#tabUsers";
            else if (tab === "borrow") target = "#tabBorrowReasons";
            else if (tab === "import") target = "#tabImportReasons";
            else if (tab === "buildings") target = "#tabBuildings";
            else if (tab === "floors") target = "#tabFloors";
            else if (tab === "departments") target = "#tabDepartments";
            else if (tab === "categories") target = "#tabCategories";
            else if (tab === "units") target = "#tabUnits";
            
            if (target) {
                const triggerEl = document.querySelector("button[data-bs-target='" + target + "']");
                if (triggerEl) {
                    const tabTrigger = new bootstrap.Tab(triggerEl);
                    tabTrigger.show();
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
