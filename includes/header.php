<?php
$base_path = isset($is_root) ? '' : '../';
$current_page = basename($_SERVER['PHP_SELF']);


// Refresh user session data to ensure the header always displays the latest information
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt_user_update = $pdo->prepare("SELECT firstname, lastname, role, image, status FROM users WHERE id = ?");
        $stmt_user_update->execute([$_SESSION['user_id']]);
        $latest_user = $stmt_user_update->fetch();
        
        if ($latest_user) {
            if (($latest_user['status'] ?? 'active') === 'suspended') {
                // Force logout if suspended
                session_destroy();
                header('Location: ' . $base_path . 'login.php?error=suspended');
                exit;
            }
            
            $_SESSION['user_name'] = $latest_user['firstname'] . ' ' . $latest_user['lastname'];
            $_SESSION['user_role'] = $latest_user['role'];
            $_SESSION['user_image'] = $latest_user['image'];
        } else {
            // User deleted
            session_destroy();
            header('Location: ' . $base_path . 'login.php');
            exit;
        }
    } catch (PDOException $e) {
        // Fallback if 'status' column doesn't exist yet (migration not run)
        $stmt_user_update = $pdo->prepare("SELECT firstname, lastname, role, image FROM users WHERE id = ?");
        $stmt_user_update->execute([$_SESSION['user_id']]);
        $latest_user = $stmt_user_update->fetch();
        if ($latest_user) {
            $_SESSION['user_name'] = $latest_user['firstname'] . ' ' . $latest_user['lastname'];
            $_SESSION['user_role'] = $latest_user['role'];
            $_SESSION['user_image'] = $latest_user['image'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'IT Asset' ?> - Inventory System</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
    <!-- JS Libraries (Load early for page scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</head>
<body>
    <script>
        // Prevent FOUC: Apply sidebar collapse state synchronously before DOM renders
        if (window.innerWidth > 992 && localStorage.getItem("sidebarCollapsedState") === "true") {
            document.body.classList.add("sidebar-is-collapsed");
        }
    </script>
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Sidebar Backdrop for mobile/tablet -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Sidebar (Only for logged in users) -->
    <div class="sidebar shadow-sm">
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <a href="<?= $base_path ?>index.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="bg-primary shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-microchip text-white fa-lg"></i>
                </div>
                <div class="ms-1">
                    <span class="fw-bold fs-4 text-dark d-block" style="line-height: 1;">BORROW</span>
                    <span class="text-muted" style="font-size: 10px; letter-spacing: 1px;">IT SYSTEM</span>
                </div>
            </a>
            <!-- Collapse Button (Desktop hamburger inside sidebar, Mobile close cross) -->
            <button id="sidebarCollapse" class="btn btn-light rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                <i class="fas fa-bars d-none d-lg-block text-muted" style="font-size: 0.95rem;"></i>
                <i class="fas fa-times d-lg-none text-muted"></i>
            </button>
        </div>

        <ul class="sidebar-menu mt-2">
            <li class="menu-item">
                <a href="<?= $base_path ?><?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>" class="menu-link <?= ($current_page == 'index.php' || $current_page == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
            <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
            <li class="menu-item">
                <a href="<?= $base_path ?>pages/borrow.php" class="menu-link <?= $current_page == 'borrow.php' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-heart"></i> <span class="menu-text">เบิกสินค้า</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="menu-item">
                <a href="<?= $base_path ?>pages/search.php" class="menu-link <?= $current_page == 'search.php' ? 'active' : '' ?>">
                    <i class="fas fa-search"></i> <span class="menu-text">ค้นหาครุภัณฑ์</span>
                </a>
            </li>
            <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
            <li class="text-muted small px-3 mt-4 mb-2 fw-bold text-uppercase" style="font-size: 10px;">คลังสินค้า</li>
            <li class="menu-item">
                <a href="<?= $base_path ?>pages/import_stock.php" class="menu-link <?= $current_page == 'import_stock.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-import"></i> <span class="menu-text">นำเข้าสินค้า</span>
                </a>
            </li>

            <li class="text-muted small px-3 mt-4 mb-2 fw-bold text-uppercase" style="font-size: 10px;">รายงาน & ตั้งค่า</li>
            <li class="menu-item">
                <a href="<?= $base_path ?>pages/reports.php" class="menu-link <?= $current_page == 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i> <span class="menu-text">รายงานทั้งหมด</span>
                </a>
            </li>
            <?php if (($_SESSION['user_role'] ?? 'USER') === 'SUPERADMIN'): ?>
            <li class="menu-item">
                <a href="javascript:void(0)" data-bs-target="#collapseSettings" class="menu-link <?= $current_page == 'settings.php' ? 'active' : '' ?> d-flex justify-content-between" data-bs-toggle="collapse" role="button" aria-expanded="false">
                    <span><i class="fas fa-cogs"></i> <span class="menu-text">จัดการข้อมูลหลัก</span></span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse" id="collapseSettings">
                    <ul class="list-unstyled ps-4 mt-1 small">
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=buildings" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? 'buildings') == 'buildings') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-building me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">อาคาร</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=floors" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'floors') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-layer-group me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">ชั้น</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=departments" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'departments') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-network-wired me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">แผนก</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=products" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'products') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-boxes me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">สินค้าในคลัง</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=users" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'users') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-users me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">ผู้ใช้งาน</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=borrow" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'borrow') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-hand-holding-heart me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">เหตุผลการเบิก</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="<?= $base_path ?>pages/settings.php?tab=import" class="menu-link py-2 <?= ($current_page == 'settings.php' && ($_GET['tab'] ?? '') == 'import') ? 'text-primary fw-bold' : 'text-muted' ?>">
                                <i class="fas fa-file-import me-2" style="font-size: 0.9rem;"></i> <span class="menu-text">เหตุผลการนำเข้า</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
        <script>
            // Set sidebar scroll position and collapse state synchronously during HTML parsing to prevent jumps
            (function() {
                try {
                    const wasExpanded = localStorage.getItem("sidebarSettingsExpanded") === "true";
                    const collapseSettings = document.getElementById("collapseSettings");
                    const toggleSettings = document.querySelector("a[data-bs-target='#collapseSettings']");
                    
                    if (wasExpanded) {
                        if (collapseSettings) {
                            collapseSettings.classList.add("show");
                        }
                        if (toggleSettings) {
                            toggleSettings.setAttribute("aria-expanded", "true");
                        }
                    }
                    
                    const sidebarMenu = document.querySelector('.sidebar-menu');
                    if (sidebarMenu) {
                        // Restore saved scroll position if it exists, otherwise fall back to active link centering
                        const savedScroll = localStorage.getItem("sidebarScrollPosition");
                        if (savedScroll !== null) {
                            sidebarMenu.scrollTop = parseInt(savedScroll, 10);
                        } else {
                            let activeMenuLink = sidebarMenu.querySelector('.collapse.show .text-primary');
                            if (!activeMenuLink) {
                                activeMenuLink = sidebarMenu.querySelector('.menu-link.active');
                            }
                            
                            if (activeMenuLink) {
                                const menuRect = sidebarMenu.getBoundingClientRect();
                                const linkRect = activeMenuLink.getBoundingClientRect();
                                const relativeTop = linkRect.top - menuRect.top;
                                
                                // If element is out of view, set scrollTop immediately
                                if (relativeTop < 0 || relativeTop + linkRect.height > menuRect.height) {
                                    sidebarMenu.scrollTop = sidebarMenu.scrollTop + relativeTop - (menuRect.height / 2) + (linkRect.height / 2);
                                }
                            }
                        }
                    }
                } catch(e) { console.error(e); }
            })();
        </script>


    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content" style="<?= !isset($_SESSION['user_id']) ? 'margin-left: 0;' : '' ?>">
        <!-- Top Navbar -->
        <!-- กรอบ 4 เหลี่ยมด้านบน <div class="top-navbar d-flex justify-content-between align-items-center mb-5 animate-up bg-white p-3 rounded-4 shadow-sm"> -->
        <div class="top-navbar d-flex justify-content-between align-items-center mb-3 animate-up py-2">
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="sidebarToggleNavbar" class="btn btn-light rounded-circle shadow-sm border p-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="fas fa-bars"></i>
                    </button>
                <?php endif; ?>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $page_title ?></h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item small"><a href="<?= $base_path ?>index.php" class="text-decoration-none">หน้าแรก</a></li>
                            <li class="breadcrumb-item small active" aria-current="page"><?= $page_title ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($current_page == 'dashboard.php'): ?>
                        <div class="dropdown">
                            <button class="btn btn-white border rounded-3 px-3 py-2 text-dark shadow-sm fw-bold dropdown-toggle d-flex align-items-center gap-2" type="button" id="dateFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.85rem; min-height: 40px;">
                                <i class="far fa-calendar text-primary"></i> <?= htmlspecialchars($period_label ?? 'วันนี้') ?> (<?= htmlspecialchars($current_date_th ?? '') ?>)
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" aria-labelledby="dateFilterDropdown" style="z-index: 1100;">
                                <li><a class="dropdown-item <?= ($f_period ?? 'today') === 'today' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['period' => 'today'])) ?>">วันนี้</a></li>
                                <li><a class="dropdown-item <?= ($f_period ?? '') === 'week' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['period' => 'week'])) ?>">สัปดาห์นี้</a></li>
                                <li><a class="dropdown-item <?= ($f_period ?? '') === 'month' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['period' => 'month'])) ?>">เดือนนี้</a></li>
                                <li><a class="dropdown-item <?= ($f_period ?? '') === 'year' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['period' => 'year'])) ?>">ปีนี้</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-3 text-decoration-none text-dark p-2 pe-3 rounded-pill shadow-sm border hover-shadow-md transition-all dropdown-toggle no-caret" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #ecfdf5; border-color: #a7f3d0 !important;">
                            <img src="<?= !empty($_SESSION['user_image']) && $_SESSION['user_image'] !== 'default_user.png' ? $base_path . 'assets/images/' . $_SESSION['user_image'] : 'https://api.dicebear.com/9.x/adventurer-neutral/svg?seed=' . urlencode($_SESSION['user_name'] ?? 'Guest') . '&backgroundColor=ecfdf5' ?>" class="rounded-circle border" width="45" height="45" alt="Profile" style="object-fit: cover; background-color: #ecfdf5;">
                            <div class="text-start d-none d-md-block">
                                <div class="fw-bold small line-height-1"><?= $_SESSION['user_name'] ?? 'Guest' ?></div>
                                <div class="text-muted" style="font-size: 10px;"><?= $_SESSION['user_role'] ?? 'User' ?></div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2" style="border-radius: 15px; min-width: 200px; z-index: 1100;">
                            <li>
                                <div class="px-3 py-2 border-bottom mb-2 d-md-none">
                                    <div class="fw-bold small"><?= $_SESSION['user_name'] ?? 'Guest' ?></div>
                                    <div class="text-muted" style="font-size: 10px;"><?= $_SESSION['user_role'] ?? 'User' ?></div>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3" href="<?= $base_path ?>pages/profile.php">
                                    <i class="fas fa-user-circle me-2 text-primary"></i>ข้อมูลส่วนตัว
                                </a>
                            </li>

                            <li><hr class="dropdown-divider opacity-50"></li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-danger" href="<?= $base_path ?>logout.php">
                                    <i class="fas fa-right-from-bracket me-2"></i>ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <style>
                    .no-caret::after { display: none !important; }
                    .dropdown-item:active { background-color: var(--bs-primary); }
                    .dropdown-item:hover { background-color: #f8f9fa; }
                    .dropdown-menu { z-index: 1060 !important; border: 1px solid rgba(0,0,0,.05) !important; position: absolute !important; }
                    .top-navbar { z-index: 1030 !important; position: relative; overflow: visible !important; }
                    .main-content { overflow: visible !important; }
                </style>
            <?php else: ?>
                <div class="d-none d-md-block text-end">
                    <div class="text-muted small fw-bold">ยินดีต้อนรับสู่ IT STOCK</div>
                    <div class="text-muted" style="font-size: 10px;">กรุณาเข้าสู่ระบบเพื่อจัดการข้อมูล</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Login Modal -->
        <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-body p-0">
                        <div class="row g-0">
                            <div class="col-12 p-5">
                                <div class="text-center mb-4">
                                    <div class="bg-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 60px; height: 60px;">
                                        <i class="fas fa-boxes-stacked text-white fa-2x"></i>
                                    </div>
                                    <h4 class="fw-bold">IT STOCK</h4>
                                    <p class="text-muted small">ระบบเบิกสินค้าและบริหารพัสดุไอที</p>
                                </div>

                                <div id="loginAlert" class="alert alert-danger small py-2 d-none"></div>

                                <form id="ajaxLoginForm">
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

                                    


                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold mb-3 rounded-pill shadow-sm">
                                        <i class="fas fa-right-to-bracket me-2"></i> เข้าสู่ระบบ
                                    </button>
                                    <div class="text-center">
                                        <span class="small text-muted">ยังไม่มีบัญชี? </span>
                                        <a href="javascript:void(0)" class="small text-primary text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#registerModal">สมัครสมาชิก</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Modal -->
        <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-body p-0">
                        <div class="row g-0">
                            <div class="col-12 p-5">
                                <div class="text-center mb-4">
                                    <div class="bg-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow-sm" style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-plus text-white fa-2x"></i>
                                    </div>
                                    <h4 class="fw-bold">สมัครสมาชิก IT STOCK</h4>
                                    <p class="text-muted small">ร่วมเป็นส่วนหนึ่งของระบบจัดการสินค้าไอที</p>
                                </div>

                                <div id="registerAlert" class="alert d-none small py-2"></div>

                                <form id="ajaxRegisterForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">ชื่อผู้ใช้ (Username)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-at text-muted"></i></span>
                                                <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="username" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">อีเมล</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                                <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="name@example.com" required>
                                            </div>
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
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">รหัสผ่าน</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                                <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label small fw-bold">ยืนยันรหัสผ่าน</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-check-double text-muted"></i></span>
                                                <input type="password" name="confirm_password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold mb-3 rounded-pill shadow-sm">
                                        <i class="fas fa-user-plus me-2"></i> สมัครสมาชิก
                                    </button>
                                    <div class="text-center">
                                        <span class="small text-muted">มีบัญชีอยู่แล้ว? </span>
                                        <a href="javascript:void(0)" class="small text-primary text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal">เข้าสู่ระบบ</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        $(document).ready(function() {
            // Login Logic
            $('#ajaxLoginForm').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const alert = $('#loginAlert');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');
                alert.addClass('d-none');
                $.ajax({
                    url: '<?= $base_path ?>login.php',
                    method: 'POST',
                    data: $(this).serialize() + '&ajax=1',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?= $base_path ?>dashboard.php';
                        } else {
                            alert.text(response.error).removeClass('d-none');
                            btn.prop('disabled', false).html('<i class="fas fa-right-to-bracket me-2"></i> เข้าสู่ระบบ');

                        }
                    },
                    error: function() {
                        alert.text('เกิดข้อผิดพลาดในการเชื่อมต่อ').removeClass('d-none');
                        btn.prop('disabled', false).html('<i class="fas fa-right-to-bracket me-2"></i> เข้าสู่ระบบ');

                    }
                });
            });

            // Register Logic
            $('#ajaxRegisterForm').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const alert = $('#registerAlert');
                
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> กำลังประมวลผล...');
                alert.addClass('d-none').removeClass('alert-danger alert-success');

                $.ajax({
                    url: '<?= $base_path ?>register.php',
                    method: 'POST',
                    data: $(this).serialize() + '&ajax=1',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert.text(response.message).addClass('alert-success').removeClass('d-none');
                            setTimeout(() => {
                                $('#registerModal').modal('hide');
                                $('#loginModal').modal('show');
                            }, 2000);
                        } else {
                            alert.text(response.error).addClass('alert-danger').removeClass('d-none');
                            btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i> สมัครสมาชิก');

                        }
                    },
                    error: function() {
                        alert.text('เกิดข้อผิดพลาด').addClass('alert-danger').removeClass('d-none');
                        btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i> สมัครสมาชิก');

                    }
                });
            });

            // Sidebar Accordion Event Listeners (Persistence storage)
            const collapseSettings = $("#collapseSettings");
            const toggleSettings = $("a[data-bs-target='#collapseSettings']");

            collapseSettings.on("shown.bs.collapse", function () {
                localStorage.setItem("sidebarSettingsExpanded", "true");
            });
            collapseSettings.on("hidden.bs.collapse", function () {
                localStorage.setItem("sidebarSettingsExpanded", "false");
            });

            // Save sidebar scroll position on user scroll to persist across reloads
            const sidebarMenu = $(".sidebar-menu");
            if (sidebarMenu.length) {
                sidebarMenu.on("scroll", function () {
                    localStorage.setItem("sidebarScrollPosition", sidebarMenu.scrollTop());
                });

                // Reset scroll position to 0 when clicking main menu items that are not settings sub-items
                sidebarMenu.find(".menu-link").not("a[data-bs-target='#collapseSettings']").on("click", function () {
                    if ($(this).closest("#collapseSettings").length === 0) {
                        localStorage.setItem("sidebarScrollPosition", "0");
                    }
                });
            }

            // (Desktop collapse state is now handled synchronously in the <body> script tag to prevent FOUC)

            // Mobile/Desktop Sidebar Toggle and Backdrop
            const sidebar = $(".sidebar");
            const backdrop = $("#sidebarBackdrop");
            
            // Navbar toggle button (Opens / expands sidebar)
            $("#sidebarToggleNavbar").on("click", function() {
                if ($(window).width() > 992) {
                    $("body").removeClass("sidebar-is-collapsed");
                    localStorage.setItem("sidebarCollapsedState", "false");
                } else {
                    sidebar.addClass("show");
                    backdrop.removeClass("d-none").addClass("show");
                    $("body").css("overflow", "hidden");
                }
            });

            // Sidebar collapse button (Toggles sidebar on desktop, Closes on mobile)
            $("#sidebarCollapse").on("click", function() {
                if ($(window).width() > 992) {
                    $("body").toggleClass("sidebar-is-collapsed");
                    localStorage.setItem("sidebarCollapsedState", $("body").hasClass("sidebar-is-collapsed"));
                } else {
                    closeSidebar();
                }
            });

            function closeSidebar() {
                sidebar.removeClass("show");
                backdrop.removeClass("show");
                setTimeout(() => {
                    if (!sidebar.hasClass("show")) {
                        backdrop.addClass("d-none");
                    }
                }, 300);
                $("body").css("overflow", "");
            }

            $("#sidebarBackdrop").on("click", closeSidebar);
            
            // Close sidebar when clicking a menu link on mobile
            $(".menu-link").on("click", function(e) {
                if ($(window).width() <= 992) {
                    if (!$(this).attr("data-bs-toggle")) {
                        closeSidebar();
                    }
                }
            });
        });
        </script>



