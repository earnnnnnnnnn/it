<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$type = $_GET['type'] ?? 'all';
$title = "รายละเอียดข้อมูล";

function formatThaiDate($dateStr, $includeTime = false) {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $day = date('j', $time);
    $month = $thai_months[(int)date('n', $time)];
    $year = (int)date('Y', $time) + 543;
    $datePart = "$day $month $year";
    if ($includeTime) {
        $datePart .= " " . date('H:i', $time) . " น.";
    }
    return $datePart;
}
$sql = "";
$params = [];

switch ($type) {
    case 'available':
        $page_title = "สินค้าพร้อมใช้งาน";
        $sql = "SELECT ps.*, p.name, p.brand, p.model, p.category, p.image, p.price,
                (SELECT asset_number FROM borrowings WHERE serial_id = ps.id ORDER BY id DESC LIMIT 1) as asset_number
                FROM product_serials ps 
                JOIN products p ON ps.product_id = p.id 
                WHERE ps.status = 'available' 
                ORDER BY ps.created_at DESC";
        break;
    case 'borrowed':
        $page_title = "สินค้าที่ถูกเบิกใช้งาน";
        $sql = "SELECT ps.*, p.name, p.brand, p.model, p.image, p.price, p.category,
                       b.id as borrow_id, b.borrowed_at, b.asset_number, b.building, b.floor, b.department, b.reason, b.image as borrow_image,
                       b.borrower_id, CONCAT(u.firstname, ' ', u.lastname) as borrower, u.image as u_image
                FROM product_serials ps 
                JOIN products p ON ps.product_id = p.id 
                JOIN borrowings b ON ps.id = b.serial_id 
                JOIN users u ON b.borrower_id = u.id 
                WHERE ps.status = 'borrowed' AND b.returned_at IS NULL
                ORDER BY b.borrowed_at DESC";
        break;
    case 'low_stock':
        $page_title = "สินค้าใกล้หมดสต็อก";
        $sql = "SELECT p.*, COUNT(ps.id) as current_stock 
                FROM products p 
                LEFT JOIN product_serials ps ON p.id = ps.product_id AND ps.status = 'available' 
                GROUP BY p.id 
                HAVING current_stock <= p.min_alert 
                ORDER BY current_stock ASC";
        break;
    default:
        $page_title = "รายการสินค้าทั้งหมด (แยกตาม Serial Number)";
        $sql = "SELECT ps.*, p.name, p.brand, p.model, p.category, p.image, p.price,
                       (SELECT asset_number FROM borrowings WHERE serial_id = ps.id ORDER BY id DESC LIMIT 1) as asset_number,
                       (SELECT COALESCE(NULLIF(CONCAT(u.firstname, ' ', u.lastname), ' '), u.fullname) 
                        FROM stock_imports si 
                        JOIN stock_import_items sii ON si.id = sii.import_id 
                        JOIN stock_import_serials sis ON sii.id = sis.import_item_id 
                        JOIN users u ON si.admin_id = u.id 
                        WHERE sis.serial_code = ps.serial_code 
                        ORDER BY si.created_at DESC LIMIT 1) as importer,
                       (SELECT si.created_at 
                        FROM stock_imports si 
                        JOIN stock_import_items sii ON si.id = sii.import_id 
                        JOIN stock_import_serials sis ON sii.id = sis.import_item_id 
                        WHERE sis.serial_code = ps.serial_code 
                        ORDER BY si.created_at DESC LIMIT 1) as import_date
                FROM product_serials ps 
                JOIN products p ON ps.product_id = p.id 
                ORDER BY ps.created_at DESC";
        $type = 'all';
}

$results = $pdo->query($sql)->fetchAll();

// Calculate total price sum and group by importer
$total_price_sum = 0;
$importer_summary = [];
if ($type != 'low_stock') {
    $total_price_sum = array_sum(array_column($results, 'price'));
    
    foreach ($results as $row) {
        $importer_name = $row['importer'] ?? 'ผู้ดูแลระบบ (System)';
        $price = $row['price'] ?? 0;
        if (!isset($importer_summary[$importer_name])) {
            $importer_summary[$importer_name] = [
                'count' => 0,
                'total_value' => 0
            ];
        }
        $importer_summary[$importer_name]['count']++;
        $importer_summary[$importer_name]['total_value'] += $price;
    }
}

require_once '../includes/header.php';
?>

<div class="card p-4 shadow-sm border-0">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold mb-1">
                <i class="fas fa-list-ul text-primary me-2"></i><?= $page_title ?>
            </h5>

        </div>
        <?php if ($type == 'all'): 
            $importers = array_unique(array_filter(array_map(function($row) {
                return $row['importer'] ?? 'ผู้ดูแลระบบ (System)';
            }, $results)));
            sort($importers);
        ?>
            <div class="d-flex align-items-center gap-2">
                <label for="importerFilter" class="form-label mb-0 text-muted small text-nowrap"><i class="fas fa-filter text-secondary me-1"></i>ตัวกรองผู้นำเข้า:</label>
                <select id="importerFilter" class="form-select form-select-sm border-secondary-subtle" style="max-width: 220px; font-size: 0.85rem;">
                    <option value="">-- แสดงทั้งหมด --</option>
                    <?php foreach ($importers as $imp): ?>
                        <option value="<?= htmlspecialchars($imp) ?>"><?= htmlspecialchars($imp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>



    <div class="table-responsive">
        <?php if ($type == 'all'): 
            // Group results by product
            $grouped = [];
            foreach ($results as $row) {
                $pid = $row['product_id'];
                if (!isset($grouped[$pid])) {
                    $grouped[$pid] = [
                        'name' => $row['name'],
                        'brand' => $row['brand'] ?? '',
                        'model' => $row['model'] ?? '',
                        'category' => $row['category'] ?? '-',
                        'image' => $row['image'],
                        'price' => $row['price'],
                        'serials' => []
                    ];
                }
                $grouped[$pid]['serials'][] = $row;
            }
        ?>
            <!-- Product Grouped View with Expandable Serials -->
            <table class="table align-middle" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>สินค้า</th>
                        <th>หมวดหมู่</th>
                        <th class="text-end">ราคาต่อหน่วย</th>
                        <th class="text-center">จำนวน S/N</th>
                        <th class="text-center">สถานะรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grouped as $pid => $product): 
                        $serial_count = count($product['serials']);
                        $available_count = count(array_filter($product['serials'], fn($s) => $s['status'] === 'available'));
                        $borrowed_count = count(array_filter($product['serials'], fn($s) => $s['status'] === 'borrowed'));
                        $other_count = $serial_count - $available_count - $borrowed_count;
                    ?>
                        <!-- Product Row (Clickable) -->
                        <tr class="product-row" 
                            data-importer="<?= htmlspecialchars($product['serials'][0]['importer'] ?? 'ผู้ดูแลระบบ (System)') ?>" 
                            data-price="<?= $product['price'] * $serial_count ?>"
                            style="cursor: pointer; transition: background-color 0.15s;"
                            onclick="toggleSerials('serials-<?= $pid ?>', this)"
                            onmouseover="this.style.backgroundColor='#f8f9ff'" 
                            onmouseout="this.style.backgroundColor=''">
                            <td class="text-center">
                                <i class="fas fa-chevron-right text-muted small transition-transform serial-chevron" id="chevron-<?= $pid ?>" style="transition: transform 0.25s ease;"></i>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" class="rounded border" width="45" height="45" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($product['brand']) ?> <?= htmlspecialchars($product['model']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($product['category']) ?></span></td>
                            <td class="text-end fw-bold text-secondary">฿<?= number_format($product['price'], 2) ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2" style="font-size: 0.85rem;">
                                    <?= $serial_count ?> รายการ
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <?php if ($available_count > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><?= $available_count ?> พร้อมใช้</span>
                                    <?php endif; ?>
                                    <?php if ($borrowed_count > 0): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><?= $borrowed_count ?> เบิกแล้ว</span>
                                    <?php endif; ?>
                                    <?php if ($other_count > 0): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><?= $other_count ?> อื่นๆ</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <!-- Serial Details (Hidden by default) -->
                        <tr id="serials-<?= $pid ?>" style="display: none;">
                            <td colspan="6" class="p-0 border-0">
                                <div style="background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%); border-left: 3px solid var(--bs-primary); margin: 0 8px 8px 8px; border-radius: 0 8px 8px 8px; padding: 16px; animation: slideDown 0.25s ease;">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fas fa-barcode text-primary"></i>
                                        <span class="fw-bold small text-primary">Serial Numbers (S/N) — <?= htmlspecialchars($product['name']) ?></span>
                                    </div>
                                    <table class="table table-sm align-middle mb-0 bg-white rounded shadow-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr class="text-muted" style="font-size: 0.75rem;">
                                                <th class="ps-3">Serial Code (S/N)</th>
                                                <th>เลขครุภัณฑ์</th>
                                                <th>ผู้นำเข้า</th>
                                                <th>วันที่นำเข้า</th>
                                                <th class="text-center">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($product['serials'] as $serial): 
                                                $display_importer = $serial['importer'] ?? 'ผู้ดูแลระบบ (System)';
                                                $display_date = $serial['import_date'] ?? $serial['created_at'];
                                                
                                                switch ($serial['status']) {
                                                    case 'available':
                                                        $status_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">พร้อมใช้งาน</span>';
                                                        break;
                                                    case 'borrowed':
                                                        $status_badge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">ถูกเบิกใช้งาน</span>';
                                                        break;
                                                    case 'repairing':
                                                        $status_badge = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">ส่งซ่อม</span>';
                                                        break;
                                                    case 'broken':
                                                        $status_badge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">ชำรุด</span>';
                                                        break;
                                                    case 'lost':
                                                        $status_badge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">สูญหาย</span>';
                                                        break;
                                                    default:
                                                        $status_badge = '<span class="badge bg-light text-dark border">ไม่ระบุ</span>';
                                                }
                                            ?>
                                                <tr>
                                                    <td class="ps-3"><code class="text-primary"><?= htmlspecialchars($serial['serial_code']) ?></code></td>
                                                    <td><span class="text-muted"><?= htmlspecialchars($serial['asset_number'] ?? '-') ?></span></td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; min-width: 24px;">
                                                                <i class="fas fa-user-tie" style="font-size: 0.65rem;"></i>
                                                            </div>
                                                            <span class="fw-bold"><?= htmlspecialchars($display_importer) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted"><?= formatThaiDate($display_date, true) ?></td>
                                                    <td class="text-center"><?= $status_badge ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type == 'low_stock'): ?>
            <!-- Product Low Stock View -->
            <table class="table align-middle table-hover" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>SKU</th>
                        <th>หมวดหมู่</th>
                        <th class="text-end">ราคาต่อหน่วย</th>
                        <th class="text-center">คงเหลือ (พร้อมใช้)</th>
                        <th class="text-center">เกณฑ์แจ้งเตือน</th>
                        <th class="text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/<?= $row['image'] ?>" class="rounded border" width="45" height="45" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($row['brand'] ?? '') ?> <?= htmlspecialchars($row['model'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($row['sku']) ?></code></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category'] ?? '-') ?></span></td>
                            <td class="text-end fw-bold text-primary">฿<?= number_format($row['price'], 2) ?></td>
                            <td class="text-center">
                                <div class="fw-bold fs-5 text-danger">
                                    <?= $row['current_stock'] ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">≤ <?= $row['min_alert'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">ใกล้หมดสต็อก</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type == 'borrowed'): 
            // Group borrowings by transaction (same borrower + borrowed_at + building + floor + department + reason)
            $transactions = [];
            foreach ($results as $row) {
                $key = $row['borrower_id'] . '|' . $row['borrowed_at'] . '|' . $row['building'] . '|' . $row['floor'] . '|' . $row['department'] . '|' . $row['reason'];
                if (!isset($transactions[$key])) {
                    $transactions[$key] = [
                        'borrower' => $row['borrower'],
                        'borrower_id' => $row['borrower_id'],
                        'u_image' => $row['u_image'] ?? 'default_user.png',
                        'borrowed_at' => $row['borrowed_at'],
                        'building' => $row['building'],
                        'floor' => $row['floor'],
                        'department' => $row['department'],
                        'reason' => $row['reason'],
                        'borrow_image' => $row['borrow_image'],
                        'items' => [],
                        'total_value' => 0,
                    ];
                }
                $transactions[$key]['items'][] = $row;
                $transactions[$key]['total_value'] += $row['price'];
            }
        ?>
            <!-- Grouped Transaction View for Borrowed Items -->
            <table class="table align-middle" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>ผู้เบิก</th>
                        <th>สถานที่</th>
                        <th>เหตุผล</th>
                        <th class="text-center">จำนวนสินค้า</th>
                        <th class="text-end">มูลค่ารวม</th>
                        <th>วันที่เบิก</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $txn_idx = 0; foreach ($transactions as $txn_key => $txn): 
                        $txn_idx++;
                        $item_count = count($txn['items']);
                        $borrow_ids = array_map(fn($item) => $item['borrow_id'], $txn['items']);
                    ?>
                        <!-- Transaction Row (Clickable) -->
                        <tr class="transaction-row" 
                            style="cursor: pointer; transition: background-color 0.15s;"
                            onclick="toggleTransaction('txn-<?= $txn_idx ?>', this)"
                            onmouseover="this.style.backgroundColor='#fffbeb'" 
                            onmouseout="if(!this.classList.contains('txn-open')) this.style.backgroundColor=''">
                            <td class="text-center">
                                <i class="fas fa-chevron-right text-muted small" id="chevron-txn-<?= $txn_idx ?>" style="transition: transform 0.25s ease;"></i>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px; background-color: #fef3c7;">
                                        <i class="fas fa-user" style="font-size: 0.85rem; color: #f59e0b;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($txn['borrower']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column" style="line-height: 1.4;">
                                    <?php if ($txn['building']): ?><span class="small"><i class="fas fa-building text-muted me-1"></i><?= htmlspecialchars($txn['building']) ?></span><?php endif; ?>
                                    <?php if ($txn['floor']): ?><span class="small text-muted"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($txn['floor']) ?></span><?php endif; ?>
                                    <?php if ($txn['department']): ?><span class="small text-muted"><i class="fas fa-users me-1"></i><?= htmlspecialchars($txn['department']) ?></span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted small"><?= htmlspecialchars($txn['reason'] ?? '-') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-white border border-warning border-opacity-25 px-3 py-2" style="font-size: 0.85rem;">
                                    <?= $item_count ?> รายการ
                                </span>
                            </td>
                            <td class="text-end fw-bold text-primary">฿<?= number_format($txn['total_value'], 2) ?></td>
                            <td class="small text-muted"><?= formatThaiDate($txn['borrowed_at'], true) ?></td>

                        </tr>
                        <!-- Transaction Details (Hidden by default) -->
                        <tr id="txn-<?= $txn_idx ?>" style="display: none;">
                            <td colspan="8" class="p-0 border-0">
                                <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 30%, #fff 100%); border-left: 3px solid #f59e0b; margin: 0 8px 8px 8px; border-radius: 0 8px 8px 8px; padding: 16px; animation: slideDown 0.25s ease;">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="fas fa-boxes-stacked text-warning"></i>
                                        <span class="fw-bold small text-dark">รายการสินค้าที่เบิก — <?= htmlspecialchars($txn['borrower']) ?></span>
                                        <span class="badge bg-warning bg-opacity-10 text-warning ms-auto"><?= $item_count ?> รายการ | ฿<?= number_format($txn['total_value'], 2) ?></span>
                                    </div>
                                    
                                    <?php if ($txn['borrow_image']): ?>
                                    <div class="mb-3 p-2 bg-white rounded-3 border d-inline-block">
                                        <div class="text-muted small mb-1"><i class="fas fa-camera me-1"></i>รูปสภาพสินค้า</div>
                                        <img src="../assets/images/<?= htmlspecialchars($txn['borrow_image']) ?>" class="rounded shadow-sm" style="max-height: 120px; max-width: 200px; object-fit: cover;" onerror="this.parentElement.style.display='none'">
                                    </div>
                                    <?php endif; ?>

                                    <table class="table table-sm align-middle mb-0 bg-white rounded shadow-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr class="text-muted" style="font-size: 0.75rem;">
                                                <th class="ps-3">สินค้า</th>
                                                <th>Serial Code (S/N)</th>
                                                <th>เลขครุภัณฑ์</th>
                                                <th class="text-end">ราคา</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($txn['items'] as $item): ?>
                                                <tr>
                                                    <td class="ps-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>" class="rounded border" width="36" height="36" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                                                <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($item['brand'] ?? '') ?> <?= htmlspecialchars($item['model'] ?? '') ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><code class="text-primary"><?= htmlspecialchars($item['serial_code']) ?></code></td>
                                                    <td><span class="text-muted"><?= htmlspecialchars($item['asset_number'] ?? '-') ?></span></td>
                                                    <td class="text-end fw-bold text-primary">฿<?= number_format($item['price'], 2) ?></td>

                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Serial View (for available type) -->
            <table class="table align-middle table-hover" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>สินค้า</th>
                        <th>Serial Code</th>
                        <th>ราคาต่อหน่วย</th>
                        <th>เลขครุภัณฑ์</th>
                        <th>วันที่นำเข้า</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/<?= $row['image'] ?>" class="rounded border" width="45" height="45" style="object-fit: cover;" onerror="this.src='../assets/images/default_product.png'">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($row['brand'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($row['serial_code']) ?></code></td>
                            <td class="text-primary fw-bold">฿<?= number_format($row['price'], 2) ?></td>
                            <td><span class="text-muted small"><?= htmlspecialchars($row['asset_number'] ?? '-') ?></span></td>
                            <td class="small text-muted"><?= formatThaiDate($row['created_at'], false) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php if (empty($results)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                <p>ไม่พบข้อมูลในหมวดนนี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
// Toggle serial number details
function toggleSerials(id, triggerRow) {
    const serialRow = document.getElementById(id);
    const pid = id.replace('serials-', '');
    const chevron = document.getElementById('chevron-' + pid);
    
    if (serialRow.style.display === 'none') {
        serialRow.style.display = '';
        chevron.style.transform = 'rotate(90deg)';
        triggerRow.style.backgroundColor = '#f0f4ff';
        triggerRow.onmouseout = null;
    } else {
        serialRow.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
        triggerRow.style.backgroundColor = '';
        triggerRow.onmouseout = function() { this.style.backgroundColor = ''; };
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('importerFilter');
    if (!filterSelect) return;
    
    const rows = document.querySelectorAll('.product-row');
    const totalCountSpan = document.getElementById('total-count-val');
    const totalPriceSpan = document.getElementById('total-price-val');
    
    filterSelect.addEventListener('change', function() {
        const selectedVal = this.value;
        let visibleCount = 0;
        let visiblePriceSum = 0;
        
        rows.forEach(row => {
            const importer = row.getAttribute('data-importer');
            const price = parseFloat(row.getAttribute('data-price')) || 0;
            // Also hide the serial details row (next sibling)
            const serialRow = row.nextElementSibling;
            
            if (selectedVal === "" || importer === selectedVal) {
                row.style.display = "";
                visibleCount++;
                visiblePriceSum += price;
            } else {
                row.style.display = "none";
                if (serialRow && serialRow.id && serialRow.id.startsWith('serials-')) {
                    serialRow.style.display = "none";
                }
            }
        });
        
        if (totalCountSpan) {
            totalCountSpan.textContent = visibleCount.toLocaleString();
        }
        const topCountSpan = document.getElementById('total-count-val-top');
        if (topCountSpan) {
            topCountSpan.textContent = visibleCount.toLocaleString();
        }
        
        if (totalPriceSpan) {
            totalPriceSpan.textContent = '฿' + visiblePriceSum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        const topPriceSpan = document.getElementById('total-price-val-top');
        if (topPriceSpan) {
            topPriceSpan.textContent = '฿' + visiblePriceSum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
});

function cancelBorrow(borrowId) {
    if (!borrowId) return;
    
    Swal.fire({
        title: 'ยืนยันการยกเลิก/ลบ?',
        html: '<div class="text-muted">รายการเบิกนี้จะถูกยกเลิกและลบออกจากระบบ<br>และสถานะสินค้าจะถูกเปลี่ยนกลับเป็น <span class="badge bg-success-subtle text-success">พร้อมใช้งาน</span></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash-can me-1"></i> ยกเลิกรายการ',
        cancelButtonText: 'ปิด',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'ajax_manage_borrow.php',
                method: 'POST',
                data: { action: 'delete', id: borrowId },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ยกเลิกสำเร็จ!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        }
    });
}

// Toggle transaction details (for grouped borrowed view)
function toggleTransaction(id, triggerRow) {
    const detailRow = document.getElementById(id);
    const chevron = document.getElementById('chevron-' + id);
    
    if (detailRow.style.display === 'none') {
        detailRow.style.display = '';
        chevron.style.transform = 'rotate(90deg)';
        triggerRow.style.backgroundColor = '#fffbeb';
        triggerRow.classList.add('txn-open');
        triggerRow.onmouseout = null;
    } else {
        detailRow.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
        triggerRow.style.backgroundColor = '';
        triggerRow.classList.remove('txn-open');
        triggerRow.onmouseout = function() { if(!this.classList.contains('txn-open')) this.style.backgroundColor = ''; };
    }
}

// Cancel all borrowings in a group/transaction
function cancelBorrowGroup(borrowIds) {
    if (!borrowIds || borrowIds.length === 0) return;
    
    Swal.fire({
        title: 'ยืนยันการยกเลิกทั้งหมด?',
        html: '<div class="text-muted">รายการเบิกทั้งหมด <strong>' + borrowIds.length + ' รายการ</strong> จะถูกยกเลิกและลบออกจากระบบ<br>และสถานะสินค้าจะถูกเปลี่ยนกลับเป็น <span class="badge bg-success-subtle text-success">พร้อมใช้งาน</span></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash-can me-1"></i> ยกเลิกทั้ง ' + borrowIds.length + ' รายการ',
        cancelButtonText: 'ปิด',
        reverseButtons: true,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            let completed = 0;
            let errors = [];
            
            // Process each borrow ID
            borrowIds.forEach(function(borrowId) {
                $.ajax({
                    url: 'ajax_manage_borrow.php',
                    method: 'POST',
                    data: { action: 'delete', id: borrowId },
                    dataType: 'json',
                    success: function(res) {
                        completed++;
                        if (!res.success) {
                            errors.push(res.message);
                        }
                        if (completed === borrowIds.length) {
                            if (errors.length === 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ยกเลิกสำเร็จ!',
                                    text: 'ยกเลิกรายการเบิกทั้ง ' + borrowIds.length + ' รายการเรียบร้อยแล้ว',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('มีข้อผิดพลาด', errors.join(', '), 'error');
                            }
                        }
                    },
                    error: function() {
                        completed++;
                        errors.push('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
                        if (completed === borrowIds.length) {
                            Swal.fire('ผิดพลาด', errors.join(', '), 'error');
                        }
                    }
                });
            });
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
