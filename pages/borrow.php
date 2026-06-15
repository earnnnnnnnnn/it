<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    header('Location: ../dashboard.php');
    exit;
}

$page_title = 'เบิกสินค้า';

// Fetch users, reasons, buildings, floors, and departments
$users = $pdo->query("SELECT id, firstname, lastname, username FROM users ORDER BY firstname ASC")->fetchAll();
$borrow_reasons = $pdo->query("SELECT * FROM reasons WHERE type = 'borrow' ORDER BY sort_order ASC, id ASC")->fetchAll();
$buildings = $pdo->query("SELECT * FROM buildings ORDER BY sort_order ASC, id ASC")->fetchAll();
$floors = $pdo->query("SELECT * FROM floors ORDER BY sort_order ASC, id ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY sort_order ASC, id ASC")->fetchAll();

require_once '../includes/header.php';
?>

<!-- Select2 for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Select2 Custom Styling for Borrow Page */
    .select2-container--default .select2-selection--single {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        height: 42px !important;
        padding: 5px 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        font-size: 0.95rem;
        line-height: 30px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #5850ec !important;
        box-shadow: 0 0 0 0.2rem rgba(88, 80, 236, 0.25) !important;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        overflow: hidden;
        margin-top: 4px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #5850ec;
        box-shadow: 0 0 0 3px rgba(88, 80, 236, 0.15);
        outline: none;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #5850ec !important;
        border-radius: 6px;
    }
    .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.9rem;
    }
    .select2-container {
        width: 100% !important;
    }
    /* Premium CSS for Borrow page matching the screenshot exactly */
    .bg-soft-blue {
        background-color: #ebf5ff;
        color: #3b82f6;
    }
    .text-purple {
        color: #5850ec;
    }
    .btn-purple {
        background-color: #5850ec;
        color: white;
        border: none;
    }
    .btn-purple:hover {
        background-color: #4f46e5;
        color: white;
    }
    .btn-purple:disabled {
        background-color: #a5b4fc;
        color: white;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.6rem 0.8rem;
        font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #5850ec;
        box-shadow: 0 0 0 0.2rem rgba(88, 80, 236, 0.25);
    }
    .input-group-joined {
        display: flex;
        align-items: stretch;
        width: 100%;
    }
    .input-group-joined .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: none;
    }
    .input-group-joined .btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Dashed Upload zone matching screenshot */
    .upload-zone {
        border: 2px dashed #cbd5e1;
        background-color: #f8fafc;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .upload-zone:hover {
        border-color: #5850ec;
        background-color: #f1f5f9;
    }
    .upload-icon {
        background-color: #ebf5ff;
        color: #3b82f6;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
    }



    /* Scanner box custom styling */
    .scanner-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    .scanner-group .scanner-icon {
        position: absolute;
        left: 1rem;
        color: #f97316;
        font-size: 1.2rem;
        z-index: 10;
    }
    .scanner-group .form-control {
        padding-left: 3rem;
        height: 52px;
        font-size: 1rem;
        background-color: #fff7ed;
        border: 1px solid #f97316;
        border-radius: 8px;
        font-weight: bold;
    }
    .scanner-group .form-control:focus {
        border-color: #f97316 !important;
        box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25) !important;
        background-color: #fff7ed !important;
    }

    /* Soft red warning alert */
    .warning-alert {
        background-color: #fef2f2;
        border: 1px solid #fee2e2;
        border-radius: 10px;
        padding: 1rem;
        color: #991b1b;
        margin-bottom: 1.5rem;
    }
    .warning-alert .alert-icon {
        background-color: #fca5a5;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Custom Floating Card Table Rows */
    .scanned-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .scanned-item-card {
        background-color: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        padding: 1rem;
        display: grid;
        grid-template-columns: 2.2fr 1.8fr 1.2fr 1.2fr 0.6fr;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease;
    }
    .scanned-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .scanned-item-card .img-container img {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        object-fit: cover;
    }
    .scanned-item-card .serial-text {
        font-family: 'Outfit', 'Inter', monospace;
        color: #db2777; /* Vibrant Pink/Magenta */
        font-weight: 700;
        font-size: 0.95rem;
    }
    .scanned-item-card .price-text {
        color: #2563eb; /* Vibrant Blue */
        font-weight: 700;
        font-size: 1rem;
    }
    .scanned-item-card .status-group {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .scanned-item-card .status-group .status-title {
        color: #ef4444; /* Soft Red */
        font-size: 0.85rem;
        font-weight: 600;
    }
    .scanned-item-card .status-group .status-time {
        color: #64748b;
        font-size: 0.75rem;
    }
    .scanned-item-card .btn-delete-card {
        background-color: #fee2e2;
        color: #ef4444;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }
    .scanned-item-card .btn-delete-card:hover {
        background-color: #fca5a5;
    }

    /* Compact summary card */
    .big-summary-card {
        background-color: #eef2ff;
        border-radius: 10px;
        padding: 0.75rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .big-summary-card .icon-container {
        background-color: #e0e7ff;
        color: #4f46e5;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    /* Table columns headers align with custom cards */
    .card-list-headers {
        display: grid;
        grid-template-columns: 2.2fr 1.8fr 1.2fr 1.2fr 0.6fr;
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    @media (max-width: 768px) {
        .card-list-headers {
            display: none !important;
        }
        .scanned-item-card {
            grid-template-columns: 1fr !important;
            gap: 0.75rem !important;
            padding: 1.25rem !important;
            text-align: center !important;
            justify-items: center !important;
        }
        .scanned-item-card .img-container {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }
        .scanned-item-card .serial-text {
            font-size: 0.85rem !important;
        }
        .scanned-item-card .status-group {
            align-items: center !important;
        }
        .scanned-item-card .text-end {
            text-align: center !important;
            width: 100% !important;
            margin-top: 0.25rem !important;
        }
    }
</style>

<div class="row g-4">
    <!-- Left Column: ข้อมูลการเบิก Form -->
    <div class="col-lg-5">
        <div class="card p-4 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-soft-blue rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                        <i class="fas fa-file-alt fs-5"></i>
                    </div>
                    <span class="fw-bold text-dark fs-5">ข้อมูลการเบิก</span>
                </div>
                <button type="button" id="btnSubmitBorrow" class="btn btn-purple fw-bold px-3 py-2 shadow-sm d-flex align-items-center gap-2" style="border-radius: 10px;" disabled>
                    <i class="fas fa-paper-plane"></i> ส่งเบิก
                </button>
            </div>
            
            <form id="borrowForm">
                <!-- เลขครุภัณฑ์ -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-dark mb-0">เลขครุภัณฑ์ <span class="text-danger">*</span></label>
                        <div class="form-check form-switch m-0 d-flex align-items-center justify-content-end" style="gap: 0.5rem;">
                            <label class="form-check-label small fw-bold text-muted mt-1" for="noAssetToggle" style="cursor: pointer; line-height: 1;">ไม่มีเลขครุภัณฑ์</label>
                            <input class="form-check-input m-0 fs-5" type="checkbox" role="switch" id="noAssetToggle" style="cursor: pointer; box-shadow: none;">
                        </div>
                    </div>
                    <div class="input-group-joined">
                        <input type="text" name="asset_number" id="asset_number" class="form-control" placeholder="7440-001-0001-60-0096" required autofocus>
                        <button type="button" class="btn btn-purple px-3" id="btnSearchAsset" title="ค้นหาครุภัณฑ์">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- ผู้เบิก -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">ผู้เบิก <span class="text-danger">*</span></label>
                    <input type="text" name="borrower_id" id="borrower_id" class="form-control" list="userList" placeholder="พิมพ์ชื่อผู้เบิก..." required autocomplete="off">
                    <datalist id="userList">
                        <?php foreach ($users as $u): ?>
                            <?php $full = trim($u['firstname'] . ' ' . $u['lastname']); ?>
                            <option value="<?= htmlspecialchars($full) ?>"><?= htmlspecialchars($full) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <!-- อาคาร / ชั้น -->
                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label small fw-bold text-dark">อาคาร <span class="text-danger">*</span></label>
                        <select id="building" class="form-select" required>
                            <option value="" disabled selected>เลือกอาคาร...</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-bold text-dark">ชั้น <span class="text-danger">*</span></label>
                        <select id="floor" class="form-select" required>
                            <option value="" disabled selected>เลือกชั้น...</option>
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- แผนก -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">แผนก <span class="text-danger">*</span></label>
                    <select id="department" class="form-select" required>
                        <option value="" disabled selected>เลือกแผนก...</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- เหตุผลการเบิก -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">เหตุผลการเบิก <span class="text-danger">*</span></label>
                    <select name="reason" id="reasonSelect" class="form-select" onchange="toggleOtherReason(this)" required>
                        <?php foreach ($borrow_reasons as $r): ?>
                            <option value="<?= htmlspecialchars($r['label']) ?>"><?= htmlspecialchars($r['label']) ?></option>
                        <?php endforeach; ?>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                
                <div class="mb-3" id="otherReasonContainer" style="display: none;">
                    <label class="form-label small fw-bold text-dark">ระบุเหตุผลอื่นๆ <span class="text-danger">*</span></label>
                    <input type="text" id="otherReason" class="form-control" placeholder="พิมพ์เหตุผลการเบิก...">
                </div>
                
                <!-- หมายเหตุ -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">หมายเหตุ</label>
                    <textarea id="notes" class="form-control" rows="2" placeholder="ระบุหมายเหตุเพิ่มเติม (ถ้ามี)..."></textarea>
                </div>
                
                <!-- ถ่ายรูปสินค้าเบิก -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">ถ่ายรูปอุปกรณ์ที่ชำรุด ขอเปลี่ยน</label>
                    <div class="upload-zone position-relative">
                        <input type="file" id="borrowImage" class="d-none" accept="image/*">
                        
                        <!-- Preview State -->
                        <div id="imagePreviewContainer" class="d-none w-100 text-center position-relative">
                            <img id="borrowImagePreview" src="" class="rounded border shadow-sm" style="max-height: 180px; max-width: 100%; object-fit: contain;">
                            <button type="button" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-1 shadow" id="btnRemoveImage" style="width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;" title="ลบรูปภาพ">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Empty State -->
                        <div id="uploadEmptyState" class="py-2">
                            <div class="upload-icon shadow-sm">
                                <i class="fas fa-camera fa-lg"></i>
                            </div>
                            <div class="small fw-bold text-dark mb-2">เพิ่มรูปภาพสินค้าเบิก</div>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary px-3 py-1.5" onclick="document.getElementById('borrowImage').click();">
                                    <i class="fas fa-upload me-1"></i> อัปโหลดไฟล์
                                </button>
                                <button type="button" class="btn btn-sm btn-purple px-3 py-1.5" id="btnStartCamera">
                                    <i class="fas fa-camera me-1"></i> ถ่ายรูป
                                </button>
                            </div>
                            <div class="text-muted mt-2" style="font-size: 10px;">รองรับไฟล์ JPG, PNG, WEBP หรือถ่ายจากกล้องโดยตรง</div>
                        </div>
                    </div>
                </div>
                

            </form>
        </div>
    </div>

    <!-- Right Column: รายการสินค้า Scanned list -->
    <div class="col-lg-7">
        <div class="card p-4 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff;">
            <!-- Header -->
            <div class="d-flex align-items-center gap-2 mb-4">
                <div class="bg-soft-blue rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                    <i class="fas fa-list-ul fs-5"></i>
                </div>
                <span class="fw-bold text-dark fs-5">รายการสินค้า</span>
            </div>
            
            <!-- Big Summary Card matching screenshot exactly -->
            <div class="big-summary-card shadow-sm mb-3" style="margin-top: 0;">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-container shadow-sm">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="text-muted small" style="font-size: 10px; line-height: 1.2;" id="bigSummaryLabel">รวมทั้งหมด</div>
                        <div class="fw-bold text-dark" id="bigItemCount" style="font-size: 1.1rem; line-height: 1.2;">0 รายการ</div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted small" style="font-size: 10px; line-height: 1.2;">มูลค่ารวมทั้งหมด</div>
                    <div class="fw-bold text-purple" id="bigTotalValue" style="font-size: 1.3rem; line-height: 1.2;">฿0.00</div>
                </div>
            </div>
            
            <!-- High-Visibility Language Warning Alert -->
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center py-3 mb-4 animate-pulse" style="background: linear-gradient(90deg, #fff5f5 0%, #fff 100%); border-left: 5px solid #f72585 !important;">
                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; min-width: 45px;">
                    <i class="fas fa-language fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-danger">โปรดตรวจสอบภาษาคีย์บอร์ด!</h6>
                    <div class="text-dark small">ต้องเปลี่ยนเป็น <span class="badge bg-danger animate-blink px-2 py-1">ENGLISH (US)</span> เท่านั้น ก่อนทำการสแกนบาร์โค้ด</div>
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group input-group-lg animate-scan-border shadow-sm">
                    <span class="input-group-text bg-orange text-white border-orange"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="scanInput" class="form-control scan-focus border-orange bg-orange-light fw-bold" placeholder="สแกน Serial ใหม่ตรงนี้..." autocomplete="off">
                </div>
                <div class="form-text mt-2 small text-orange"><i class="fas fa-info-circle me-1"></i>ระบบรองรับการสแกนต่อเนื่อง (Continuous Scan)</div>
            </div>
            
            <!-- Headers -->
            <div class="card-list-headers">
                <div>สินค้า</div>
                <div>SERIAL / BARCODE</div>
                <div>ราคา</div>
                <div>สถานะ</div>
                <div class="text-end" style="padding-right: 12px;">จัดการ</div>
            </div>
            
            <!-- Card List Container -->
            <div class="scanned-list" id="borrowList">
                <div id="emptyRow" class="text-center text-muted py-5 bg-light rounded-3 border border-dashed">
                    <i class="fas fa-shopping-basket fa-3x mb-3 opacity-25 text-purple"></i>
                    <p class="fw-bold mb-1">กรุณาสแกน Serial ของสินค้าที่ต้องการเบิก</p>
                    <p class="small text-muted mb-0">ระบบจะทำการสแกนและตรวจสอบสถานะโดยอัตโนมัติ</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item History Timeline Card -->
<div class="card p-4 mt-4 border-0 shadow-sm" id="itemHistoryCard" style="display: none; background: linear-gradient(180deg, #ffffff 0%, #f9fbfd 100%); border-radius: 12px;">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
        <div class="d-flex align-items-center gap-3" id="historyItemHeader">
            <!-- Header info will be injected here -->
        </div>
        <button type="button" class="btn-close" onclick="$('#itemHistoryCard').hide();" aria-label="Close"></button>
    </div>
    <div class="timeline-container px-3">
        <div class="list-group list-group-flush" id="historyTimeline">
            <!-- Timeline items will be injected here -->
        </div>
    </div>
</div>

<!-- Camera Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-camera me-2 text-primary"></i>ถ่ายรูปด้วยกล้อง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <!-- Video Stream Container -->
                <div class="position-relative overflow-hidden rounded border bg-dark mb-3 mx-auto" style="aspect-ratio: 4/3; max-width: 100%; max-height: 320px;">
                    <video id="webcamVideo" autoplay playsinline muted class="w-100 h-100" style="object-fit: cover;"></video>
                    <!-- Loading or Error overlays -->
                    <div id="cameraLoading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <div class="small">กำลังเปิดกล้อง...</div>
                    </div>
                </div>
                
                <canvas id="webcamCanvas" class="d-none"></canvas>
                
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-purple fw-bold px-4" id="btnCapturePhoto">
                        <i class="fas fa-circle me-1"></i> ถ่ายรูป
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">ค้นหาครุภัณฑ์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group mb-4">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                    <input type="text" id="modalSearchInput" class="form-control bg-light border-start-0" placeholder="พิมพ์ชื่อสินค้า, ยี่ห้อ, รุ่น หรือ Serial...">
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>สินค้า</th>
                                <th>Serial</th>
                                <th>ราคา</th>
                                <th>สถานะ</th>
                                <th width="80"></th>
                            </tr>
                        </thead>
                        <tbody id="modalSearchResult">
                            <!-- Results will appear here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleOtherReason(selectElement) {
        if ($(selectElement).val() === "อื่นๆ") {
            $("#otherReasonContainer").slideDown();
            $("#otherReason").prop("required", true).focus();
        } else {
            $("#otherReasonContainer").slideUp();
            $("#otherReason").prop("required", false).val("");
        }
    }

    $(document).ready(function() {
        // Toggle for no asset number
        $('#noAssetToggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#asset_number').val('ไม่มีเลขครุภัณฑ์').prop('readonly', true);
            } else {
                if ($('#asset_number').val() === 'ไม่มีเลขครุภัณฑ์') {
                    $('#asset_number').val('');
                }
                $('#asset_number').prop('readonly', false).focus();
            }
        });

        // Initialize Select2 for searchable dropdowns
        $('#building').select2({ width: '100%' });
        $('#floor').select2({ width: '100%' });
        $('#department').select2({ width: '100%' });

        // Auto-fill location & show history summary when a borrower is selected
        $('#borrower_id').on('input change', function() {
            const borrowerName = $(this).val().trim();
            if (borrowerName) {
                $.ajax({
                    url: 'ajax_get_user_location.php',
                    method: 'GET',
                    data: { name: borrowerName },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.data) {
                            // Update the select elements if they are currently empty or default
                            if (res.data.building && (!$('#building').val() || $('#building').val() === "")) {
                                $('#building').val(res.data.building).trigger('change');
                            }
                            if (res.data.floor && (!$('#floor').val() || $('#floor').val() === "")) {
                                $('#floor').val(res.data.floor).trigger('change');
                            }
                            if (res.data.department && (!$('#department').val() || $('#department').val() === "")) {
                                $('#department').val(res.data.department).trigger('change');
                            }
                            
                            // Update and show borrower history summary box IF cart is empty
                            if (selectedSerials.size === 0) {
                                const count = res.data.borrowed_count || 0;
                                const value = parseFloat(res.data.borrowed_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
                                
                                $('#bigSummaryLabel').text('ประวัติการยืม (ยังไม่คืน)');
                                $('#bigTotalLabel').text('มูลค่ารวมที่ยืมอยู่');
                                $('#bigItemCount').text(count + " รายการ").removeClass('text-dark').addClass('text-danger');
                                $('#bigTotalValue').text('฿' + value).removeClass('text-purple').addClass('text-danger');
                            }
                        } else if (selectedSerials.size === 0) {
                            $('#bigSummaryLabel').text('รวมทั้งหมด');
                            $('#bigTotalLabel').text('มูลค่ารวมทั้งหมด');
                            $('#bigItemCount').text("0 รายการ").removeClass('text-danger').addClass('text-dark');
                            $('#bigTotalValue').text("฿0.00").removeClass('text-danger').addClass('text-purple');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                    }
                });
            } else if (selectedSerials.size === 0) {
                $('#bigSummaryLabel').text('รวมทั้งหมด');
                $('#bigTotalLabel').text('มูลค่ารวมทั้งหมด');
                $('#bigItemCount').text("0 รายการ").removeClass('text-danger').addClass('text-dark');
                $('#bigTotalValue').text("฿0.00").removeClass('text-danger').addClass('text-purple');
            }
        });

        const selectedSerials = new Map();
        let borrowImageBase64 = null;

        // Handle Image Select and Base64 Conversion
        $("#borrowImage").on("change", function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({ icon: 'warning', title: 'ไฟล์มีขนาดใหญ่เกินไป', text: 'กรุณาอัปโหลดรูปภาพขนาดไม่เกิน 5MB' });
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(evt) {
                borrowImageBase64 = evt.target.result;
                $("#borrowImagePreview").attr("src", borrowImageBase64);
                $("#imagePreviewContainer").removeClass("d-none");
                $("#uploadEmptyState").addClass("d-none");
            };
            reader.readAsDataURL(file);
        });

        // Handle Image Removal
        $("#btnRemoveImage").on("click", function() {
            borrowImageBase64 = null;
            $("#borrowImage").val("");
            $("#borrowImagePreview").attr("src", "");
            $("#imagePreviewContainer").addClass("d-none");
            $("#uploadEmptyState").removeClass("d-none");
        });

        // --- Camera / Webcam Logic ---
        let cameraStream = null;

        // Start webcam stream
        $("#btnStartCamera").on("click", function() {
            // Open camera modal
            $("#cameraModal").modal("show");
            
            $("#cameraLoading").removeClass("d-none").html(`
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="small">กำลังเปิดกล้อง...</div>
            `);
            $("#webcamVideo").addClass("d-none");

            // Request camera stream
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment', // prefer back camera
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            })
            .then(function(stream) {
                cameraStream = stream;
                const video = document.getElementById("webcamVideo");
                video.srcObject = stream;
                video.onloadedmetadata = function() {
                    video.play();
                    $("#cameraLoading").addClass("d-none");
                    $("#webcamVideo").removeClass("d-none");
                };
            })
            .catch(function(err) {
                // Fallback to any available video source if environment camera fails
                navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    cameraStream = stream;
                    const video = document.getElementById("webcamVideo");
                    video.srcObject = stream;
                    video.onloadedmetadata = function() {
                        video.play();
                        $("#cameraLoading").addClass("d-none");
                        $("#webcamVideo").removeClass("d-none");
                    };
                })
                .catch(function(e) {
                    $("#cameraLoading").html(`
                        <div class="text-danger mb-2"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                        <div class="small fw-bold">ไม่สามารถเปิดใช้งานกล้องได้</div>
                        <div class="text-muted" style="font-size: 10px;">กรุณาอนุญาตให้สิทธิ์เข้าถึงกล้องถ่ายภาพ</div>
                    `);
                    console.error("Camera access error:", e);
                });
            });
        });

        // Capture photo from webcam
        $("#btnCapturePhoto").on("click", function() {
            const video = document.getElementById("webcamVideo");
            const canvas = document.getElementById("webcamCanvas");
            if (!video || !canvas || !cameraStream) return;

            // Set canvas size to match the video feed
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext("2d");
            
            // Draw current video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Get base64 string
            borrowImageBase64 = canvas.toDataURL("image/jpeg", 0.85);

            // Update preview and UI states
            $("#borrowImagePreview").attr("src", borrowImageBase64);
            $("#imagePreviewContainer").removeClass("d-none");
            $("#uploadEmptyState").addClass("d-none");

            // Close the modal
            $("#cameraModal").modal("hide");
        });

        // Stop camera tracks when modal is hidden
        $("#cameraModal").on("hidden.bs.modal", function() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            const video = document.getElementById("webcamVideo");
            if (video) {
                video.srcObject = null;
            }
        });

        // Add to main list when clicking the search button
        $("#btnSearchAsset").click(function() {
            const val = $("#asset_number").val().trim();
            if (!val) {
                Swal.fire({ icon: "warning", title: "กรุณากรอกข้อมูล", timer: 1000, showConfirmButton: false });
                return;
            }
            $.ajax({
                url: "ajax_search_available.php",
                method: "GET",
                data: { q: val },
                success: function(res) {
                    const result = JSON.parse(res);
                    if (result.length === 0) {
                        Swal.fire({ icon: "info", title: "ไม่พบข้อมูล", timer: 1000, showConfirmButton: false });
                        return;
                    }
                    
                    // Auto-fill form fields with the latest borrower details of the first result
                    if (result[0].borrower_name) {
                        if (!$("#borrower_id").val()) $("#borrower_id").val(result[0].borrower_name);
                        if (!$("#building").val()) $("#building").val(result[0].building || '').trigger('change');
                        if (!$("#floor").val()) $("#floor").val(result[0].floor || '').trigger('change');
                        if (!$("#department").val()) $("#department").val(result[0].department || '').trigger('change');
                        
                        if (result[0].reason) {
                            let r = result[0].reason;
                            let exists = false;
                            $("#reasonSelect option").each(function() {
                                if ($(this).val() === r) exists = true;
                            });
                            if (exists) {
                                $("#reasonSelect").val(r);
                                $("#otherReasonContainer").hide();
                            } else {
                                $("#reasonSelect").val("อื่นๆ");
                                $("#otherReasonContainer").show();
                                $("#otherReason").val(r);
                            }
                        }
                        
                        // Trigger change to update borrower info box
                        $("#borrower_id").trigger('change');
                    }
                    
                    result.forEach(item => {
                        const code = item.serial_code;
                        if ($(`#row-${code}`).length > 0) return; // Skip if already in table
                        
                        const isAvailable = item.status === "available";
                        
                        // Auto-add to selectedSerials if available
                        if (isAvailable) {
                            selectedSerials.set(code, item);
                        }
                        
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + " น.";
                        
                        let statusText = isAvailable ? "ว่าง" : "ถูกเบิก";
                        let statusColor = isAvailable ? "#10b981" : "#ef4444";
                        let statusSubtext = isAvailable ? "" : (item.borrower_name || "ไม่ทราบชื่อ");

                        const row = $(`
                            <div id="row-${code}" class="scanned-item-card ${!isAvailable ? 'opacity-75' : ''}">
                                <div class="d-flex align-items-center gap-3 img-container">
                                    <img src="../assets/images/${item.image}" width="50" height="50" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div style="line-height: 1.2;">
                                        <div class="fw-bold text-dark fs-6">${item.name}</div>
                                        <div class="text-muted small">${item.brand} ${item.model}</div>
                                    </div>
                                </div>
                                <div class="serial-text"><code>${code}</code></div>
                                <div class="price-text">฿${parseFloat(item.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                <div class="status-group">
                                    <span class="status-title" style="color: ${statusColor};">${statusText}</span>
                                    <span class="status-time">${statusSubtext}</span>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn-delete-card remove-item" data-code="${code}" title="ลบรายการ">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        `);
                        $("#emptyRow").hide();
                        $("#borrowList").prepend(row);
                    });
                    updateUI();
                    $("#scanInput").focus();
                }
            });
        });

        // Handle adding item from search results
        $(document).on("click", ".add-from-search", function() {
            const itemStr = $(this).attr("data-item");
            const item = JSON.parse(itemStr);
            const code = item.serial_code;
            
            selectedSerials.set(code, item);
            
            // Change button to Trash icon
            $(this).replaceWith(`
                <button type="button" class="btn-delete-card remove-item" data-code="${code}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `);
            updateUI();
        });

        function updateUI() {
            let total = 0;
            selectedSerials.forEach(item => {
                total += parseFloat(item.price || 0);
            });

            if (selectedSerials.size > 0) {
                $("#emptyRow").hide();
                $("#btnSubmitBorrow").prop("disabled", false);
                
                // Show cart summary
                $('#bigSummaryLabel').text('รวมทั้งหมด');
                $('#bigTotalLabel').text('มูลค่ารวมทั้งหมด');
                $("#bigItemCount").text(selectedSerials.size + " รายการ").removeClass('text-danger').addClass('text-dark');
                $("#bigTotalValue").text("฿" + total.toLocaleString(undefined, {minimumFractionDigits: 2})).removeClass('text-danger').addClass('text-purple');
            } else {
                $("#emptyRow").show();
                $("#btnSubmitBorrow").prop("disabled", true);
                
                // Try to show borrower history if borrower is selected
                const borrowerName = $('#borrower_id').val();
                if (borrowerName && borrowerName.trim() !== "") {
                    $('#borrower_id').trigger('change');
                } else {
                    $('#bigSummaryLabel').text('รวมทั้งหมด');
                    $('#bigTotalLabel').text('มูลค่ารวมทั้งหมด');
                    $("#bigItemCount").text("0 รายการ").removeClass('text-danger').addClass('text-dark');
                    $("#bigTotalValue").text("฿0.00").removeClass('text-danger').addClass('text-purple');
                }
            }
        }

        $(document).on("barcodeScanned", function(e, code) {
            lookupSerial(code);
        });

        // --- Auto-English Keyboard Layout Conversion Logic ---
        function enableAutoEnglishInput(selector) {
            const codeToChar = {
                'KeyQ': 'q', 'KeyW': 'w', 'KeyE': 'e', 'KeyR': 'r', 'KeyT': 't', 'KeyY': 'y', 'KeyU': 'u', 'KeyI': 'i', 'KeyO': 'o', 'KeyP': 'p',
                'KeyA': 'a', 'KeyS': 's', 'KeyD': 'd', 'KeyF': 'f', 'KeyG': 'g', 'KeyH': 'h', 'KeyJ': 'j', 'KeyK': 'k', 'KeyL': 'l',
                'KeyZ': 'z', 'KeyX': 'x', 'KeyC': 'c', 'KeyV': 'v', 'KeyB': 'b', 'KeyN': 'n', 'KeyM': 'm',
                'Digit1': '1', 'Digit2': '2', 'Digit3': '3', 'Digit4': '4', 'Digit5': '5', 'Digit6': '6', 'Digit7': '7', 'Digit8': '8', 'Digit9': '9', 'Digit0': '0',
                'Minus': '-', 'Equal': '=', 'BracketLeft': '[', 'BracketRight': ']', 'Backslash': '\\',
                'Semicolon': ';', 'Quote': "'", 'Comma': ',', 'Period': '.', 'Slash': '/'
            };

            const codeToCharShift = {
                'KeyQ': 'Q', 'KeyW': 'W', 'KeyE': 'E', 'KeyR': 'R', 'KeyT': 'T', 'KeyY': 'Y', 'KeyU': 'U', 'KeyI': 'I', 'KeyO': 'O', 'KeyP': 'P',
                'KeyA': 'A', 'KeyS': 'S', 'KeyD': 'D', 'KeyF': 'F', 'KeyG': 'G', 'KeyH': 'H', 'KeyJ': 'J', 'KeyK': 'K', 'KeyL': 'L',
                'KeyZ': 'Z', 'KeyX': 'X', 'KeyC': 'C', 'KeyV': 'V', 'KeyB': 'B', 'KeyN': 'N', 'KeyM': 'M',
                'Digit1': '!', 'Digit2': '@', 'Digit3': '#', 'Digit4': '$', 'Digit5': '%', 'Digit6': '^', 'Digit7': '&', 'Digit8': '*', 'Digit9': '(', 'Digit0': ')',
                'Minus': '_', 'Equal': '+', 'BracketLeft': '{', 'BracketRight': '}', 'Backslash': '|',
                'Semicolon': ':', 'Quote': '"', 'Comma': '<', 'Period': '>', 'Slash': '?'
            };

            const thaiToEngMap = {
                'ๆ': 'q', 'ไ': 'w', 'ำ': 'e', 'พ': 'r', 'ะ': 't', 'ั': 'y', 'ี': 'u', 'ร': 'i', 'น': 'o', 'ย': 'p', 'บ': '[', 'ล': ']', 'ฃ': '\\',
                'ฟ': 'a', 'ห': 's', 'ก': 'd', 'ด': 'f', 'เ': 'g', '้': 'h', '่': 'j', 'า': 'k', 'ส': 'l', 'ว': ';', 'ง': "'",
                'ผ': 'z', 'ป': 'x', 'แ': 'c', 'อ': 'v', 'ิ': 'b', 'ื': 'n', 'ท': 'm', 'ม': ',', 'ใ': '.', 'ฝ': '/',
                'ๅ': '1', '/': '2', '_': '3', 'ภ': '4', 'ถ': '5', 'ุ': '6', 'ึ': '7', 'ค': '8', 'ต': '9', 'จ': '0', 'ข': '-', 'ช': '=',
                '๐': 'Q', '"': 'W', 'ฎ': 'E', 'ฏ': 'R', 'ธ': 'T', 'ํ': 'Y', '๊': 'U', 'ณ': 'I', 'ฯ': 'O', 'ญ': 'P', 'ฐ': '{', '': '}',
                'ฤ': 'A', 'ฆ': 'S', 'ฏ': 'D', 'ฌ': 'F', '็': 'G', '๋': 'H', 'ษ': 'K', 'ศ': 'L', 'ซ': ':', 'ง': '"',
                'ผ': 'Z', 'ป': 'X', 'ฉ': 'C', 'ฮ': 'V', 'ิ': 'B', 'ื': 'N', 'ท': 'M', 'ม': '<', 'ใ': '>', 'ฝ': '?',
                '+': '!', '๑': '@', '๒': '#', '๓': '$', '๔': '%', 'ู': '^', '฿': '&', '๕': '*', '๖': '(', '๗': ')',
                '๘': '_', '๙': '+'
            };

            $(document).on('keydown', selector, function(e) {
                if (e.ctrlKey || e.altKey || e.metaKey || e.key === 'Tab' || e.key === 'Enter' || e.key === 'Backspace' || e.key.startsWith('Arrow')) {
                    return;
                }

                const code = e.originalEvent.code;
                const mappedChar = e.shiftKey ? codeToCharShift[code] : codeToChar[code];

                if (mappedChar !== undefined) {
                    e.preventDefault();
                    
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    const val = $(this).val();
                    const newVal = val.substring(0, start) + mappedChar + val.substring(end);
                    $(this).val(newVal);
                    
                    this.selectionStart = this.selectionEnd = start + 1;
                    $(this).trigger('input');
                }
            });

            $(document).on('input', selector, function() {
                const val = $(this).val();
                let newVal = '';
                let hasThai = false;
                
                for (let i = 0; i < val.length; i++) {
                    const char = val[i];
                    if (thaiToEngMap[char] !== undefined) {
                        newVal += thaiToEngMap[char];
                        hasThai = true;
                    } else {
                        newVal += char;
                    }
                }
                
                if (hasThai) {
                    const start = this.selectionStart;
                    $(this).val(newVal);
                    this.selectionStart = this.selectionEnd = start;
                }
            });
        }

        enableAutoEnglishInput('#scanInput');

        let scanTimeout = null;
        $("#scanInput").on("input", function() {
            clearTimeout(scanTimeout);
            const inputEl = $(this);
            scanTimeout = setTimeout(function() {
                const code = inputEl.val().trim();
                if (code.length > 2) {
                    lookupSerial(code);
                    inputEl.val("").focus();
                }
            }, 200);
        });

        $("#scanInput").on("keydown", function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(scanTimeout);
                const code = $(this).val().trim();
                if (code.length > 2) {
                    lookupSerial(code);
                    $(this).val("").focus();
                }
            }
        });

        function lookupSerial(code) {
            // Sweep and clear all already borrowed items from the list first
            selectedSerials.forEach((item, k) => {
                if (item.status !== "available") {
                    selectedSerials.delete(k);
                    $(`#row-${k}`).remove();
                }
            });
            updateUI();

            if (selectedSerials.has(code)) {
                // Item is already in the cart
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'สแกนซ้ำ!',
                    text: 'รหัสนี้ถูกเพิ่มในรายการเบิกแล้ว',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                $(`#row-${code}`).addClass('table-warning');
                setTimeout(() => $(`#row-${code}`).removeClass('table-warning'), 1500);
                return;
            }

            const existingRow = $(`#row-${code}`);
            if (existingRow.length > 0) {
                const addBtn = existingRow.find('.add-from-search');
                if (addBtn.length > 0) {
                    // It's a search result and is available! Auto-add it to cart.
                    addBtn.click();
                    return;
                } else {
                    // It's in the table but not in cart and not available to add -> must be already borrowed.
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'ถูกเบิกไปแล้ว!',
                        text: 'รหัสนี้ไม่สามารถเบิกได้ (ติดสถานะถูกเบิก)',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    
                    existingRow.addClass('table-danger');
                    setTimeout(() => existingRow.removeClass('table-danger'), 1500);
                    return;
                }
            }

            $.ajax({
                url: "ajax_lookup_serial.php",
                method: "GET",
                data: { code: code },
                dataType: "json",
                success: function(res) {
                    if (res.success) {
                        const isAvailable = res.data.status === "available";
                        
                        // Auto-fill form fields with the latest borrower details
                        if (res.data.borrower_name) {
                            if (!$("#borrower_id").val()) $("#borrower_id").val(res.data.borrower_name);
                            if (!$("#building").val()) $("#building").val(res.data.building || '').trigger('change');
                            if (!$("#floor").val()) $("#floor").val(res.data.floor || '').trigger('change');
                            if (!$("#department").val()) $("#department").val(res.data.department || '').trigger('change');
                            
                            if (res.data.reason) {
                                let r = res.data.reason;
                                let exists = false;
                                $("#reasonSelect option").each(function() {
                                    if ($(this).val() === r) exists = true;
                                });
                                if (exists) {
                                    $("#reasonSelect").val(r);
                                    $("#otherReasonContainer").hide();
                                } else {
                                    $("#reasonSelect").val("อื่นๆ");
                                    $("#otherReasonContainer").show();
                                    $("#otherReason").val(r);
                                }
                            }
                            
                            // Trigger change to update borrower info box
                            $("#borrower_id").trigger('change');
                        }
                        
                        if (!isAvailable) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'ถูกเบิกไปแล้ว!',
                                text: `สินค้านี้ถูกเบิกโดย ${res.data.borrower_name || 'ผู้อื่น'} แล้ว`,
                                timer: 3000,
                                showConfirmButton: false
                            });
                            // If it exists in selectedSerials, delete it and remove the row
                            if (selectedSerials.has(code)) {
                                selectedSerials.delete(code);
                                $(`#row-${code}`).remove();
                                updateUI();
                            }
                            return; // Do not add already borrowed items!
                        }
                        
                        selectedSerials.set(code, res.data);
                        
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + " น.";
                        
                        let statusText = isAvailable ? "รอดำเนินการ" : "ถูกเบิก";
                        let statusColor = isAvailable ? "#ef4444" : "#64748b";
                        let statusSubtext = isAvailable ? timeStr : (res.data.borrower_name || "ไม่ทราบชื่อ");

                        const row = $(`
                            <div id="row-${code}" class="scanned-item-card ${!isAvailable ? 'opacity-75' : ''}">
                                <div class="d-flex align-items-center gap-3 img-container">
                                    <img src="../assets/images/${res.data.image}" width="50" height="50" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div style="line-height: 1.2;">
                                        <div class="fw-bold text-dark fs-6">${res.data.name}</div>
                                        <div class="text-muted small">${res.data.brand} ${res.data.model}</div>
                                    </div>
                                </div>
                                <div class="serial-text"><code>${code}</code></div>
                                <div class="price-text">฿${parseFloat(res.data.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                <div class="status-group">
                                    <span class="status-title" style="color: ${statusColor};">${statusText}</span>
                                    <span class="status-time">${statusSubtext}</span>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn-delete-card remove-item" data-code="${code}" title="ลบรายการ">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        `);
                        $("#emptyRow").hide();
                        $("#borrowList").prepend(row);
                        
                        updateUI();
                    } else {
                        Swal.fire({ icon: "error", title: "ไม่พบรหัส", text: "ไม่พบรหัส " + code + " ในระบบ", timer: 2500, showConfirmButton: false });
                    }
                }
            });
        }

        $(document).on("click", ".remove-item", function() {
            const code = $(this).data("code");
            selectedSerials.delete(code);
            $(`#row-${code}`).remove();
            updateUI();
        });

        $("#btnSubmitBorrow").click(function() {
            const borrower_id = $("#borrower_id").val().trim();
            const asset_number = $("#asset_number").val().trim();
            const building = $("#building").val().trim();
            const floor = $("#floor").val().trim();
            const department = $("#department").val().trim();
            let reason = $("#reasonSelect").val();

            if (!borrower_id || !asset_number || !building || !floor || !department || !reason) {
                Swal.fire({ icon: "warning", title: "ข้อมูลไม่ครบ", text: "กรุณากรอกข้อมูลให้ครบทุกช่องที่มีเครื่องหมาย *" });
                return;
            }

            if (reason === "อื่นๆ") {
                reason = $("#otherReason").val().trim();
                if (!reason) {
                    Swal.fire({ icon: "warning", title: "ข้อมูลไม่ครบ", text: "กรุณาระบุเหตุผลอื่นๆ" });
                    return;
                }
            }

            const notes = $("#notes").val().trim();

            const data = {
                borrower_id: borrower_id,
                asset_number: asset_number,
                building: building,
                floor: floor,
                department: department,
                reason: reason,
                notes: notes,
                serials: Array.from(selectedSerials.keys()),
                image: borrowImageBase64
            };

            $.ajax({
                url: "ajax_process_borrow.php",
                method: "POST",
                data: JSON.stringify(data),
                contentType: "application/json",
                success: function(res) {
                    const result = JSON.parse(res);
                    if (result.success) {
                        Swal.fire({ icon: "success", title: "เบิกสำเร็จ", text: "บันทึกข้อมูลการเบิกแล้ว" }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: "error", title: "ผิดพลาด", text: result.message });
                    }
                }
            });
        });
    });

    function viewHistory() {
        const val = document.getElementById("asset_number").value.trim();
        if (!val) {
            Swal.fire({ icon: "warning", title: "กรุณากรอกข้อมูลค้นหา", timer: 1000, showConfirmButton: false });
            return;
        }

        $.ajax({
            url: "ajax_item_history.php",
            method: "GET",
            data: { q: val },
            success: function(res) {
                const data = JSON.parse(res);
                if (data.error) {
                    Swal.fire({ icon: "error", title: "ไม่พบข้อมูล", text: data.error });
                    return;
                }

                $("#historyTimeline").empty();
                
                if (data.type === "item") {
                    const item = data.item;
                    $("#historyItemHeader").html(`
                        <img src="../assets/images/${item.image}" class="rounded border shadow-sm" width="50" height="50" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                        <div>
                            <div class="fw-bold text-primary">ประวัติของ: ${item.name}</div>
                            <div class="text-muted small">${item.brand} ${item.model} | Serial: ${item.serial_code}</div>
                        </div>
                    `);

                    data.history.forEach(h => {
                        let conditionImgHtml = "";
                        if (h.condition_image) {
                            conditionImgHtml = `
                                <div class="mt-2 ps-5">
                                    <span class="text-muted small d-block mb-1"><i class="fas fa-camera me-1"></i>สภาพสินค้าตอนเบิก:</span>
                                    <img src="../assets/images/${h.condition_image}" class="rounded border shadow-sm" style="max-height: 80px; max-width: 150px; object-fit: cover; cursor: pointer;" onclick="window.open('../assets/images/${h.condition_image}', '_blank')" title="คลิกดูรูปภาพใหญ่">
                                </div>
                            `;
                        }
                        $("#historyTimeline").append(`
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="../assets/images/${h.user_image}" class="rounded-circle border" width="30" height="30" style="object-fit: cover;" onerror="this.src='../assets/images/default_user.png'">
                                        <span class="fw-bold small">${h.firstname} ${h.lastname}</span>
                                    </div>
                                    <span class="badge bg-light text-dark fw-normal" style="font-size: 10px;">${h.borrow_date}</span>
                                </div>
                                <div class="small text-muted ps-5">เหตุผล: ${h.reason}</div>
                                ${conditionImgHtml}
                            </div>
                        `);
                    });
                } else if (data.type === "user") {
                    const user = data.user;
                    $("#historyItemHeader").html(`
                        <img src="../assets/images/${user.image}" class="rounded-circle border shadow-sm" width="50" height="50" style="object-fit: cover;" onerror="this.src='../assets/images/default_user.png'">
                        <div>
                            <div class="fw-bold text-success">ประวัติของ: ${user.firstname} ${user.lastname}</div>
                            <div class="text-muted small">@${user.username} (ค้นพบจากรายชื่อผู้เบิก)</div>
                        </div>
                    `);

                    data.history.forEach(h => {
                        let conditionImgHtml = "";
                        if (h.condition_image) {
                            conditionImgHtml = `
                                <div class="mt-2 ps-5">
                                    <span class="text-muted small d-block mb-1"><i class="fas fa-camera me-1"></i>สภาพสินค้าตอนเบิก:</span>
                                    <img src="../assets/images/${h.condition_image}" class="rounded border shadow-sm" style="max-height: 80px; max-width: 150px; object-fit: cover; cursor: pointer;" onclick="window.open('../assets/images/${h.condition_image}', '_blank')" title="คลิกดูรูปภาพใหญ่">
                                </div>
                            `;
                        }
                        $("#historyTimeline").append(`
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="../assets/images/${h.product_image}" class="rounded border" width="30" height="30" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                        <span class="fw-bold small">${h.product_name}</span>
                                    </div>
                                    <span class="badge bg-light text-dark fw-normal" style="font-size: 10px;">${h.borrow_date}</span>
                                </div>
                                <div class="small text-muted ps-5">${h.brand} ${h.model} | เหตุผล: ${h.reason}</div>
                                ${conditionImgHtml}
                            </div>
                        `);
                    });
                }

                if (data.history.length === 0) {
                    $("#historyTimeline").append('<div class="text-center py-4 text-muted">ยังไม่เคยมีประวัติการเบิก</div>');
                }

                $("#itemHistoryCard").show();
                $("html, body").animate({
                    scrollTop: $("#itemHistoryCard").offset().top - 100
                }, 500);
            }
        });
    }

    function prefillSearch() {
        const val = document.getElementById("asset_number").value;
        if (val) {
            $("#rightSearchInput").val(val).trigger("input");
        }
    }

    function formatAssetNumber(input) {
        let val = input.value.replace(/[^0-9-]/g, "");
        if (val.length > 4 && val[4] !== "-") val = val.slice(0, 4) + "-" + val.slice(4);
        if (val.length > 8 && val[8] !== "-") val = val.slice(0, 8) + "-" + val.slice(8);
        input.value = val;
    }
</script>
<?php require_once '../includes/footer.php'; ?>