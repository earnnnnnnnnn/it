<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Auto-migrate database columns if they don't exist
try { $pdo->exec("ALTER TABLE products ADD COLUMN rental_price DECIMAL(10,2) DEFAULT '0.00' AFTER price"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE products ADD COLUMN rental_duration DATE NULL AFTER rental_price"); } catch (PDOException $e) {}
try { $pdo->exec("UPDATE products SET rental_duration = NULL WHERE rental_duration = '0'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE products MODIFY COLUMN rental_duration DATE NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE products ADD COLUMN remark TEXT NULL"); } catch (PDOException $e) {}

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    header('Location: ../dashboard.php');
    exit;
}

$page_title = 'เพิ่มสินค้าใหม่';

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
$units = $pdo->query("SELECT * FROM units ORDER BY sort_order ASC, id ASC")->fetchAll();

// Fetch distinct product types to populate the dropdown
try {
    $product_types_db = $pdo->query("SELECT * FROM product_types ORDER BY sort_order ASC, id ASC")->fetchAll();
    $product_types = array_column($product_types_db, 'name');
} catch (Exception $e) {
    $existing_types = $pdo->query("SELECT DISTINCT name FROM products WHERE name IS NOT NULL AND name != '' ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    $default_types = ['Keyboard', 'Mouse', 'จอคอมพิวเตอร์'];
    $product_types = array_unique(array_merge($default_types, $existing_types));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Handle Image Upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('กรุณาเลือกภาพสินค้าก่อนบันทึก');
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = 'prod_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $image_name)) {
            throw new Exception('ไม่สามารถอัปโหลดรูปสินค้าได้ กรุณาลองอีกครั้ง');
        }

        // 2. Insert Product
        $product_name = $_POST['name'];
        if ($product_name === 'อื่นๆ' && !empty($_POST['other_name'])) {
            $product_name = $_POST['other_name'];
        }

        $stmt = $pdo->prepare("INSERT INTO products (sku, name, category, brand, model, spec, price, rental_price, rental_duration, unit, min_alert, image, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['sku'], $product_name, $_POST['category'], $_POST['brand'], 
            $_POST['model'], $_POST['spec'], $_POST['price'], 
            $_POST['rental_price'] ?? 0, !empty($_POST['rental_duration']) ? $_POST['rental_duration'] : null, 
            $_POST['unit'], $_POST['min_alert'],
            'products/' . $image_name,
            $_POST['remark'] ?? null
        ]);
        $product_id = $pdo->lastInsertId();

        // 3. Insert Serials and Create Import Record
        if (isset($_POST['serials']) && !empty($_POST['serials'])) {
            $serials = array_unique(array_filter($_POST['serials']));
            
            // Create Stock Import Record
            $reason = $_POST['import_reason'];
            if ($reason === 'อื่นๆ' && !empty($_POST['other_reason'])) {
                $reason = $_POST['other_reason'];
            }
            
            $stmt_import = $pdo->prepare("INSERT INTO stock_imports (admin_id, reason) VALUES (?, ?)");
            $stmt_import->execute([$_SESSION['user_id'], $reason]);
            $import_id = $pdo->lastInsertId();
            
            $stmt_item = $pdo->prepare("INSERT INTO stock_import_items (import_id, product_id, qty) VALUES (?, ?, ?)");
            $stmt_item->execute([$import_id, $product_id, count($serials)]);
            $item_id = $pdo->lastInsertId();

            $stmt_serial = $pdo->prepare("INSERT INTO product_serials (product_id, serial_code) VALUES (?, ?)");
            $stmt_sis = $pdo->prepare("INSERT INTO stock_import_serials (import_item_id, serial_code) VALUES (?, ?)");
            
            foreach ($serials as $sc) {
                $stmt_serial->execute([$product_id, $sc]);
                $stmt_sis->execute([$item_id, $sc]);
            }
        }

        $pdo->commit();
        $success = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<?php if (isset($success)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ',
        text: 'สินค้าใหม่ถูกเพิ่มเข้าระบบแล้ว',
        timer: 2000,
        showConfirmButton: false
    }).then(() => { window.location = 'dashboard.php'; });
</script>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST" id="productForm" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- Left Column: Product Info -->
        <div class="col-lg-7">
            <div class="card p-4">
                <h6 class="fw-bold mb-4"><i class="fas fa-info-circle text-primary me-2"></i>ข้อมูลหลักสินค้า</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Barcode หลัก (SKU) <span class="text-danger">*</span></label>
                        <input type="text" name="sku" class="form-control scan-focus border-orange bg-orange-light" placeholder="เช่น SKU-001" value="<?= isset($_GET['sku']) ? htmlspecialchars($_GET['sku']) : '' ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">ประเภทสินค้า <span class="text-danger">*</span></label>
                        <select name="name" id="productName" class="form-select" onchange="toggleOtherProductName(this)" required>
                            <option value="">-- เลือกประเภทสินค้า --</option>
                            <?php foreach ($product_types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                            <option value="อื่นๆ">อื่นๆ (เพิ่มใหม่)</option>
                        </select>
                    </div>
                    <div class="col-12" id="otherProductNameContainer" style="display: none;">
                        <label class="form-label small fw-bold text-danger">ระบุประเภทสินค้าอื่นๆ <span class="text-danger">*</span></label>
                        <input type="text" name="other_name" id="otherProductName" class="form-control border-danger" placeholder="พิมพ์ประเภทสินค้า...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ยี่ห้อ <span class="text-danger">*</span></label>
                        <input type="text" name="brand" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">รุ่น <span class="text-danger">*</span></label>
                        <input type="text" name="model" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">สเปก / รายละเอียด</label>
                        <textarea name="spec" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ราคาต่อหน่วย (บาท) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" class="form-control" value="0.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">ราคาเช่า (บาท)</label>
                        <input type="number" step="0.01" name="rental_price" class="form-control" value="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">วันสิ้นสุดการเช่า</label>
                        <input type="date" name="rental_duration" class="form-control" value="">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">หน่วยนับ <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required>
                            <option value="">-- เลือกหน่วยนับ --</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">แจ้งเตือนขั้นต่ำ <span class="text-danger">*</span></label>
                        <input type="number" name="min_alert" class="form-control" value="5" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">เหตุผลการนำเข้า <span class="text-danger">*</span></label>
                        <select name="import_reason" id="importReason" class="form-select" onchange="toggleOtherReason(this)" required>
                            <option value="จัดซื้อใหม่">จัดซื้อใหม่</option>
                            <option value="รับบริจาค">รับบริจาค</option>
                            <option value="ย้ายมาจากสาขาอื่น">ย้ายมาจากสาขาอื่น</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="col-12" id="otherReasonContainer" style="display: none;">
                        <label class="form-label small fw-bold text-danger">ระบุเหตุผลอื่นๆ <span class="text-danger">*</span></label>
                        <input type="text" name="other_reason" id="otherReason" class="form-control border-danger" placeholder="พิมพ์เหตุผลการนำเข้า...">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">หมายเหตุ (ถ้ามี)</label>
                        <textarea name="remark" class="form-control" rows="2" placeholder="ใส่หมายเหตุเพิ่มเติม..."></textarea>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">รูปสินค้า <span class="text-danger">*</span></label>
                        <input id="imageInput" type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div id="imagePreview" class="border rounded bg-light d-flex align-items-center justify-content-center" style="width: 100%; height: 38px; overflow: hidden;">
                            <span class="text-muted small">ไม่มีรูป</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Serials -->
        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0"><i class="fas fa-barcode text-primary me-2"></i>รายการ Serial / Barcode แยกชิ้น</h6>
                    <span class="badge bg-primary rounded-pill" id="serialCount">0</span>
                </div>

                <!-- High-Visibility Language Warning Alert -->
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center py-3 mb-4 animate-pulse" style="background: linear-gradient(90deg, #fff5f5 0%, #fff 100%); border-left: 5px solid #f72585 !important;">
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; min-width: 45px;">
                        <i class="fas fa-language fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-danger">โปรดตรวจสอบภาษาคีย์บอร์ด!</h6>
                        <div class="text-dark small">ต้องเปลี่ยนเป็น <span class="badge bg-danger animate-blink px-2 py-1">ENGLISH (US)</span> เท่านั้น ก่อนทำการสแกน</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group input-group-lg animate-scan-border shadow-sm">
                        <span class="input-group-text bg-orange text-white border-orange"><i class="fas fa-qrcode"></i></span>
                        <input type="text" id="serialInput" class="form-control scan-focus border-orange bg-orange-light fw-bold" placeholder="สแกน Barcode ตรงนี้..." autofocus autocomplete="off">
                    </div>
                    <div class="form-text mt-2 small text-orange"><i class="fas fa-info-circle me-1"></i>ระบบรองรับการสแกนต่อเนื่อง (Continuous Scan)</div>
                </div>

                <div id="serialList" class="overflow-auto px-1" style="max-height: 350px;">
                    <!-- Latest Scan Info -->
                    <div id="latestScan" class="d-none mb-3 p-2 border border-primary bg-primary bg-opacity-10 rounded animate-bounce-in">
                        <div class="text-primary small fw-bold mb-1">ยิงล่าสุด:</div>
                        <div class="h5 fw-bold mb-0 text-dark" id="latestScanCode"></div>
                    </div>

                    <!-- Empty State -->
                    <div class="text-center text-muted py-5" id="emptySerial">
                        <i class="fas fa-barcode fa-3x mb-3 opacity-25"></i>
                        <p class="small">ยังไม่มีข้อมูล Serial<br>กรุณาสแกนหรือกรอกข้อมูล</p>
                    </div>
                    <!-- Items container -->
                    <div id="serialItems"></div>
                </div>
            </div>
        </div>

        <div class="col-12 text-center mt-4">
            <hr>
            <button type="button" id="btnSubmitForm" class="btn btn-primary px-5 py-2 fw-bold" disabled>
                <i class="fas fa-save me-2"></i> บันทึกข้อมูลครุภัณฑ์
            </button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        const serials = new Set();

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

        enableAutoEnglishInput('input[name="sku"]');
        enableAutoEnglishInput('#serialInput');

        // If SKU is pre-filled from URL parameter, focus on the product name input
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sku')) {
            $('[name="name"]').focus();
        }

        function updateSerialList(latest = null) {
            $('#serialItems').empty();
            if (serials.size === 0) {
                $('#emptySerial').removeClass('d-none');
                $('#latestScan').addClass('d-none');
                $('#serialCount').text(0);
                return;
            }

            $('#emptySerial').addClass('d-none');
            
            if (latest) {
                $('#latestScan').removeClass('d-none');
                $('#latestScanCode').text(latest);
            }

            let index = serials.size;
            serials.forEach(code => {
                const item = $(`
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 mb-2 border-0 shadow-sm animate-fade-in" style="border: 1px solid #eee !important;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-barcode fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6">${code}</div>
                                <div class="text-muted" style="font-size: 10px;">รายการที่ ${index--}</div>
                            </div>
                        </div>
                        <input type="hidden" name="serials[]" value="${code}">
                        <button type="button" class="btn btn-link text-danger p-0 remove-serial" data-code="${code}" style="text-decoration: none;">
                            <i class="far fa-trash-alt fs-5"></i>
                        </button>
                    </div>
                `);
                $('#serialItems').prepend(item);
            });
            $('#serialCount').text(serials.size);
        }

        // Function to process the scanned serial
        function processSerial() {
            const inputEl = $('#serialInput');
            const code = inputEl.val().trim();
            
            if (code.length < 3) return;

            // Clear input immediately to prepare for next scan
            inputEl.val('').focus();

            if (!serials.has(code)) {
                serials.add(code);
                updateSerialList(code);
                
                // Visual feedback (Flash the input box)
                inputEl.addClass('bg-success bg-opacity-10');
                setTimeout(() => inputEl.removeClass('bg-success bg-opacity-10'), 300);

                // Success Toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1200,
                    timerProgressBar: false
                });
                Toast.fire({
                    icon: 'success',
                    title: 'สแกนสำเร็จ: ' + code
                });
            } else {
                // Warning Toast (Non-blocking)
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'warning',
                    title: 'Serial ซ้ำ',
                    text: 'รหัสนี้มีอยู่ในรายการแล้ว'
                });
            }
        }

        let scanTimeout = null;
        
        // Listen to multiple events for maximum reliability
        $('#serialInput').on('input paste keyup', function(e) {
            // If Enter was pressed, keydown handles it immediately, so skip keyup
            if (e.type === 'keyup' && e.which === 13) return;

            clearTimeout(scanTimeout);
            scanTimeout = setTimeout(processSerial, 100); 
        });
        
        // Handle Enter key for immediate processing
        $('#serialInput').on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(scanTimeout);
                processSerial();
            }
        });

        // Global barcode listener (for when input is not focused)
        $(document).on('barcodeScanned', function(e, code) {
            if (code.length > 2) {
                if (!serials.has(code)) {
                    serials.add(code);
                    updateSerialList(code);
                } else {
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    Toast.fire({ icon: 'warning', title: 'Serial ซ้ำ' });
                }
            }
        });

        $(document).on('click', '.remove-serial', function() {
            const code = $(this).attr('data-code');
            serials.delete(code);
            updateSerialList();
        });

        // Prevent form submission on Enter key entirely
        $('#productForm').on('keydown', function(e) {
            if (e.which === 13 && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        });

        function updateSaveButtonState() {
            const hasImage = $('#imageInput')[0] && $('#imageInput')[0].files.length > 0;
            $('#btnSubmitForm').prop('disabled', !hasImage);
        }

        // Manual submit button click
        $('#btnSubmitForm').click(function() {
            const hasImage = $('#imageInput')[0] && $('#imageInput')[0].files.length > 0;
            if (!hasImage) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรูปภาพ',
                    text: 'กรุณาแนบรูปสินค้าเพื่อบันทึกข้อมูล'
                });
                return;
            }

            if ($('input[name="sku"]').val() && $('[name="name"]').val()) {
                $('#productForm').submit();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ข้อมูลไม่ครบ',
                    text: 'กรุณากรอกข้อมูลที่จำเป็น (SKU และ ประเภทสินค้า)'
                });
            }
        });

        // Image Preview
        $('#imageInput').change(function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').html(`<img src="${e.target.result}" style="height: 100%; width: auto;">`);
                };
                reader.readAsDataURL(file);
            } else {
                $('#imagePreview').html('<span class="text-muted small">ไม่มีรูป</span>');
            }
            updateSaveButtonState();
        });

        updateSaveButtonState();
    });

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

    function toggleOtherProductName(el) {
        var container = document.getElementById('otherProductNameContainer');
        var input = document.getElementById('otherProductName');
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
