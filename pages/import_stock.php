<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    header('Location: ../dashboard.php');
    exit;
}

$page_title = 'นำเข้าสินค้าเดิม';

// Fetch all products for selection with available count
$products = $pdo->query("
    SELECT p.id, p.name, p.sku, p.brand, p.model, p.image,
    (SELECT COUNT(*) FROM product_serials ps WHERE ps.product_id = p.id AND ps.status = 'available') as available_count
    FROM products p 
    ORDER BY p.name ASC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0"><i class="fas fa-file-import text-primary me-2"></i>เลือกสินค้าเพื่อนำเข้า</h6>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">สแกนบาร์โค้ดสินค้า (SKU)</label>
                <div class="input-group input-group-lg animate-scan-border shadow-sm">
                    <span class="input-group-text bg-orange text-white border-orange"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="skuScanInput" class="form-control scan-focus border-orange bg-orange-light fw-bold" placeholder="ยิงบาร์โค้ดสินค้าตรงนี้..." autofocus autocomplete="off">
                </div>
                <div class="form-text mt-2 small text-orange"><i class="fas fa-info-circle me-1"></i>สแกน SKU เพื่อเลือกสินค้าอัตโนมัติ หรือเลือกจากรายการด้านล่าง</div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">หรือ เลือกสินค้าจากรายการ</label>
                <div class="dropdown custom-product-dropdown">
                    <button class="btn btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center bg-white py-2 shadow-sm" type="button" id="dropdownProductBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="selectedProductText">-- เลือกสินค้าจากรายการ --</span>
                        <i class="fas fa-chevron-down small text-muted"></i>
                    </button>
                    <div class="dropdown-menu w-100 shadow-lg border-0 mt-1 py-0 overflow-hidden" aria-labelledby="dropdownProductBtn" style="max-height: 400px;">
                        <div class="p-2 bg-light border-bottom sticky-top">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="productSearchInput" class="form-control border-start-0" placeholder="ค้นหาชื่อสินค้า หรือ SKU..." autocomplete="off">
                            </div>
                        </div>
                        <div id="productListItems" class="overflow-auto" style="max-height: 330px;">
                            <?php foreach ($products as $p): ?>
                                <div class="dropdown-item p-2 border-bottom product-option" 
                                     data-id="<?= $p['id'] ?>" 
                                     data-name="<?= htmlspecialchars($p['name']) ?>" 
                                     data-sku="<?= htmlspecialchars($p['sku']) ?>" 
                                     data-image="<?= htmlspecialchars($p['image']) ?>"
                                     data-count="<?= $p['available_count'] ?>"
                                     data-brand="<?= htmlspecialchars($p['brand']) ?>"
                                     data-model="<?= htmlspecialchars($p['model']) ?>"
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="../assets/images/<?= $p['image'] ?>" class="rounded border" width="45" height="45" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                            <div style="line-height: 1.2;">
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                                <div class="text-muted mb-1" style="font-size: 10px;"><?= htmlspecialchars($p['brand']) ?> <?= htmlspecialchars($p['model']) ?></div>
                                                <div class="text-primary" style="font-size: 11px;"><?= htmlspecialchars($p['sku']) ?></div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-muted small" style="font-size: 10px;">คงเหลือ</div>
                                            <div class="badge bg-<?= $p['available_count'] > 0 ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $p['available_count'] > 0 ? 'success' : 'danger' ?> rounded-pill"><?= $p['available_count'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="productSelect" value="">
            </div>

            <div id="productDetail" class="bg-light p-3 rounded mb-4 d-none animate-fade-in border">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <img id="selectedProdImage" src="" class="rounded border bg-white shadow-sm" width="70" height="70" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                        <div>
                            <div class="text-muted small fw-bold">สินค้าที่เลือก:</div>
                            <div class="fw-bold fs-6 text-dark" id="selectedProdName">-</div>
                            <div class="text-muted small mb-1" id="selectedProdBrandModel" style="font-size: 0.8rem;"></div>
                            <div class="badge bg-primary-subtle text-primary border border-primary border-opacity-25" id="selectedProdSKU">-</div>
                        </div>
                    </div>
                    <div class="text-end bg-white p-2 rounded border px-3">
                        <div class="text-muted small fw-bold">คงเหลือในระบบ</div>
                        <div class="fs-4 fw-bold text-dark" id="selectedProdCount">0</div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">เหตุผลการนำเข้า</label>
                <select id="importReason" class="form-select" onchange="toggleOtherReason(this)">
                    <option value="จัดซื้อใหม่">จัดซื้อใหม่</option>
                    <option value="รับบริจาค">รับบริจาค</option>
                    <option value="ย้ายมาจากสาขาอื่น">ย้ายมาจากสาขาอื่น</option>
                    <option value="คืนจากโครงการ">คืนจากโครงการ</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </div>
            
            <div class="mb-4" id="otherReasonContainer" style="display: none;">
                <label class="form-label small fw-bold text-danger">ระบุเหตุผลอื่นๆ <span class="text-danger">*</span></label>
                <input type="text" id="otherReason" class="form-control border-danger" placeholder="พิมพ์เหตุผลการนำเข้า...">
            </div>

            <button type="button" id="btnSubmitImport" class="btn btn-primary w-100 py-2 fw-bold" disabled>
                <i class="fas fa-save me-2"></i> บันทึกการนำเข้า
            </button>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0"><i class="fas fa-barcode text-primary me-2"></i>สแกนเพิ่ม Serial ใหม่</h6>
                <span class="badge bg-primary rounded-pill" id="serialCount">0</span>
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
                    <input type="text" id="serialInput" class="form-control scan-focus border-orange bg-orange-light fw-bold" placeholder="สแกน Serial ใหม่ตรงนี้..." autocomplete="off">
                </div>
                <div class="form-text mt-2 small text-orange"><i class="fas fa-info-circle me-1"></i>ระบบรองรับการสแกนต่อเนื่อง (Continuous Scan)</div>
            </div>

            <div id="serialList" class="overflow-auto px-1" style="max-height: 400px;">
                <div class="text-center text-muted py-5" id="emptySerial">
                    <i class="fas fa-barcode fa-3x mb-3 opacity-25"></i>
                    <p>กรุณาเลือกสินค้าและสแกน Serial</p>
                </div>
                <div id="serialItems"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add New Product (Simple) -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-box me-2"></i>ลงทะเบียนสินค้าใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="row g-3">
                        <!-- Left: Info -->
                        <div class="col-lg-7">
                            <div class="card p-3 border-0 shadow-sm">
                                <h6 class="fw-bold mb-3 small"><i class="fas fa-info-circle text-primary me-2"></i>ข้อมูลหลักสินค้า</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Barcode หลัก (SKU) <span class="text-danger">*</span></label>
                                        <input type="text" name="sku" class="form-control form-control-sm scan-focus border-orange bg-orange-light" placeholder="เช่น SKU-001" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control form-control-sm" placeholder="เช่น Logitech G Pro" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                                        <select name="category" class="form-select form-select-sm" required>
                                            <option value="IT">IT Gadget</option>
                                            <option value="Office">Office Supplies</option>
                                            <option value="Network">Network Equipment</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small fw-bold">ยี่ห้อ <span class="text-danger">*</span></label>
                                        <input type="text" name="brand" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small fw-bold">รุ่น <span class="text-danger">*</span></label>
                                        <input type="text" name="model" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">สเปก / รายละเอียด</label>
                                        <textarea name="spec" class="form-control form-control-sm" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">ราคาต่อหน่วย <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="0.00" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">หน่วยนับ <span class="text-danger">*</span></label>
                                        <select name="unit" class="form-select form-select-sm" required>
                                            <option value="ชิ้น">ชิ้น</option>
                                            <option value="ตัว">ตัว</option>
                                            <option value="เครื่อง">เครื่อง</option>
                                            <option value="ชุด">ชุด</option>
                                            <option value="กล่อง">กล่อง</option>
                                            <option value="อัน">อัน</option>
                                            <option value="ม้วน">ม้วน</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">แจ้งเตือนขั้นต่ำ <span class="text-danger">*</span></label>
                                        <input type="number" name="min_alert" class="form-control form-control-sm" value="5" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">รูปสินค้า <span class="text-danger">*</span></label>
                                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Right: Serials -->
                        <div class="col-lg-5">
                            <div class="card p-3 border-0 shadow-sm h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 small"><i class="fas fa-barcode text-primary me-2"></i>ยิง Serial ใหม่</h6>
                                    <span class="badge bg-primary rounded-pill" id="modalSerialCount">0</span>
                                </div>
                                <div class="mb-3">
                                    <input type="text" id="modalSerialInput" class="form-control form-control-sm border-orange bg-orange-light fw-bold" placeholder="สแกน Serial ตรงนี้...">
                                </div>
                                <div id="modalSerialList" class="overflow-auto" style="max-height: 250px;">
                                    <div id="modalSerialItems"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="submitNewProduct()">
                    <i class="fas fa-save me-1"></i>บันทึกสินค้า
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const serials = new Set();
        let selectedProductId = null;

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
                // Ignore functional keys
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

        enableAutoEnglishInput('#skuScanInput');
        enableAutoEnglishInput('#serialInput');
        enableAutoEnglishInput('#modalSerialInput');

        // --- Custom Dropdown Search Logic ---
        $('#productSearchInput').on('keyup', function() {
            const val = $(this).val().toLowerCase();
            $('#productListItems .product-option').each(function() {
                const name = $(this).data('name').toLowerCase();
                const sku = $(this).data('sku').toLowerCase();
                $(this).toggle(name.includes(val) || sku.includes(val));
            });
        });

        function setProduct(id, name, sku, image, count, brand, model) {
            selectedProductId = id;
            $('#productSelect').val(id);
            $('#selectedProductText').text(name);
            $('#selectedProdName').text(name);
            $('#selectedProdBrandModel').text(brand + ' ' + model);
            $('#selectedProdSKU').text(sku);
            $('#selectedProdCount').text(count);
            $('#selectedProdImage').attr('src', '../assets/images/' + image);
            $('#productDetail').removeClass('d-none');
            $('#btnSubmitImport').prop('disabled', false);
            
            // Highlight active in dropdown
            $('.product-option').removeClass('bg-primary bg-opacity-10');
            $(`.product-option[data-id="${id}"]`).addClass('bg-primary bg-opacity-10');
        }

        $(document).on('click', '.product-option', function() {
            const d = $(this).data();
            setProduct(d.id, d.name, d.sku, d.image, d.count, d.brand, d.model);
        });

        // --- SKU Barcode Scan Logic ---
        function selectProductBySKU(sku) {
            sku = sku.trim();
            let found = false;
            $('#productListItems .product-option').each(function() {
                const optSku = $(this).data('sku');
                if (optSku && optSku.toString().toLowerCase() === sku.toLowerCase()) {
                    const d = $(this).data();
                    setProduct(d.id, d.name, d.sku, d.image, d.count, d.brand, d.model);
                    found = true;
                    // Focus #serialInput immediately and prevent Swal from stealing/returning focus
                    setTimeout(() => $('#serialInput').focus(), 10);
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'พบสินค้า!', 
                        text: d.name, 
                        timer: 800, 
                        showConfirmButton: false,
                        returnFocus: false
                    });
                    return false; // break
                }
            });
            if (!found) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบสินค้าในระบบ!',
                    text: 'กำลังพาท่านไปหน้าลงทะเบียนสินค้าใหม่สำหรับ SKU: ' + sku,
                    timer: 800,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'add_product.php?sku=' + encodeURIComponent(sku);
                });
            }
            $('#skuScanInput').val('');
        }

        // Handle Enter key on SKU input
        $('#skuScanInput').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const code = $(this).val().trim();
                if (code) selectProductBySKU(code);
            }
        });

        function processSerial() {
            const inputEl = $('#serialInput');
            const code = inputEl.val().trim();
            if (code.length > 2) {
                if (!selectedProductId) {
                    Swal.fire({ icon: 'info', title: 'แจ้งเตือน', text: 'กรุณาเลือกสินค้าก่อนสแกน Serial' });
                    inputEl.val('').focus();
                    return;
                }

                if (serials.has(code)) {
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    Toast.fire({ icon: 'warning', title: 'Serial ซ้ำ', text: 'รหัสนี้อยู่ในรายการที่จะนำเข้าแล้ว' });
                    inputEl.val('').focus();
                } else {
                    // Check database for global duplicate
                    $.ajax({
                        url: 'ajax_lookup_serial.php',
                        method: 'GET',
                        data: { code: code },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                // Serial exists in DB
                                Swal.fire({ 
                                    icon: 'error', 
                                    title: 'รหัสซ้ำในระบบ!', 
                                    html: `เลข <b>${code}</b> ถูกบันทึกไว้ในระบบแล้ว<br>สินค้า: ${res.data.name}<br>โปรดตรวจสอบความถูกต้อง`,
                                    timer: 1500,
                                    showConfirmButton: false,
                                    returnFocus: false
                                });
                            } else {
                                // Not in DB, add it
                                serials.add(code);
                                renderSerials();
                            }
                            inputEl.val('').focus();
                        }
                    });
                }
            }
        }

        // Handle manual entry / Scanner with debounce
        let scanTimeout = null;
        $('#serialInput').on('input', function() {
            clearTimeout(scanTimeout);
            scanTimeout = setTimeout(processSerial, 200);
        });

        // Handle Enter key for immediate processing
        $('#serialInput').on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(scanTimeout);
                processSerial();
            }
        });

        // Handle global barcode scan when SKU input is focused
        $(document).on('barcodeScanned', function(e, code) {
            if ($('#skuScanInput').is(':focus')) {
                selectProductBySKU(code);
                return;
            }
            
            if (!selectedProductId) {
                Swal.fire({ icon: 'info', title: 'แจ้งเตือน', text: 'กรุณาเลือกสินค้าก่อนสแกน Serial' });
                return;
            }

            if (serials.has(code)) {
                Swal.fire({ icon: 'warning', title: 'Serial ซ้ำ', text: 'รหัสนี้อยู่ในรายการที่จะนำเข้าแล้ว', timer: 1000, showConfirmButton: false });
                return;
            }

            // Check database for global duplicate
            $.ajax({
                url: 'ajax_lookup_serial.php',
                method: 'GET',
                data: { code: code },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'รหัสซ้ำในระบบ!', 
                            html: `เลข <b>${code}</b> ถูกบันทึกไว้ในระบบแล้ว<br>สินค้า: ${res.data.name}`,
                            timer: 1500,
                            showConfirmButton: false,
                            returnFocus: false
                        });
                    } else {
                        serials.add(code);
                        renderSerials();
                        $('#serialInput').val('').focus();
                    }
                }
            });
        });

        // Selection is now handled by setProduct() via custom dropdown



        function renderSerials() {
            $('#serialItems').empty();
            if (serials.size === 0) {
                $('#emptySerial').removeClass('d-none');
            } else {
                $('#emptySerial').addClass('d-none');
                let index = serials.size;
                serials.forEach(code => {
                    $('#serialItems').prepend(`
                        <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded mb-2 border shadow-sm animate-fade-in">
                            <div class="d-flex align-items-center">
                                <div class="bg-orange text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 28px; height: 28px; font-size: 11px;">
                                    ${index--}
                                </div>
                                <div>
                                    <div class="font-monospace fw-bold text-dark" style="font-size: 0.85rem;">${code}</div>
                                </div>
                            </div>
                            <button class="btn btn-sm text-danger remove-serial" data-code="${code}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `);
                });
            }
            $('#serialCount').text(serials.size);
        }

        $(document).on('click', '.remove-serial', function() {
            serials.delete($(this).data('code'));
            renderSerials();
        });

        $('#btnSubmitImport').click(function() {
            if (serials.size === 0) {
                Swal.fire({ icon: 'warning', title: 'ไม่มีข้อมูล', text: 'กรุณาสแกน Serial อย่างน้อย 1 รายการ' });
                return;
            }

            let reason = $('#importReason').val();
            if (reason === 'อื่นๆ') {
                reason = $('#otherReason').val().trim();
                if (!reason) {
                    Swal.fire({ icon: 'warning', title: 'ข้อมูลไม่ครบ', text: 'กรุณาระบุเหตุผลอื่นๆ' });
                    return;
                }
            }

            const data = {
                product_id: selectedProductId,
                reason: reason,
                serials: Array.from(serials)
            };

            $.ajax({
                url: 'ajax_process_import.php',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    const result = JSON.parse(res);
                    if (result.success) {
                        Swal.fire({ icon: 'success', title: 'นำเข้าสำเร็จ' }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: result.message });
                    }
                }
            });
        });
    });

    const modalSerials = new Set();
    function updateModalSerialList() {
        $('#modalSerialItems').empty();
        let index = modalSerials.size;
        modalSerials.forEach(code => {
            $('#modalSerialItems').prepend(`
                <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded mb-1 border shadow-sm small">
                    <div class="fw-bold text-dark">${code}</div>
                    <button type="button" class="btn btn-link text-danger p-0 remove-modal-serial" data-code="${code}"><i class="fas fa-times"></i></button>
                </div>
            `);
        });
        $('#modalSerialCount').text(modalSerials.size);
    }
    $(document).on('click', '.remove-modal-serial', function() {
        modalSerials.delete($(this).data('code'));
        updateModalSerialList();
    });
    $('#modalSerialInput').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const code = $(this).val().trim();
            if (code && !modalSerials.has(code)) {
                modalSerials.add(code);
                updateModalSerialList();
                $(this).val('').focus();
            }
        }
    });

    function addProduct() {
        modalSerials.clear();
        updateModalSerialList();
        new bootstrap.Modal(document.getElementById('addProductModal')).show();
    }

    function submitNewProduct() {
        var form = document.getElementById('newProductForm');
        var formData = new FormData(form);
        if (modalSerials.size > 0) {
            formData.append('serials', Array.from(modalSerials).join(','));
        }

        fetch('ajax_manage_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: 'ลงทะเบียนสินค้าเรียบร้อยแล้ว' }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
            }
        });
    }

    function toggleOtherReason(el) {
        var container = document.getElementById('otherReasonContainer');
        var input = document.getElementById('otherReason');
        if (el.value === 'อื่นๆ') {
            container.style.display = 'block';
            input.focus();
        } else {
            container.style.display = 'none';
            input.value = '';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
