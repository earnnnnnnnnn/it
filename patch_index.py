import re

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# We want to replace everything from <div id="explore" ...> to </style> before <?php require_once 'includes/footer.php'; ?>

start_marker = '<div id="explore" class="row g-4 mb-4">'
end_marker = '</style>'

start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx) + len(end_marker)

new_html = """<!-- Select2 for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .stat-card-new {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
    }
    .stat-card-new:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    }
    .stat-icon-container {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .trend-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .trend-up {
        background-color: #ecfdf5;
        color: #10b981;
    }
    .trend-down {
        background-color: #fef2f2;
        color: #ef4444;
    }
    .chart-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .list-item-new {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8fafc;
    }
    .list-item-new:last-child {
        border-bottom: none;
    }
    .blue-gradient-card {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        border-radius: 16px;
        color: white;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.15);
    }
    .select2-container--default .select2-selection--single {
        background: transparent !important;
        border: none !important;
        height: 34px !important;
        padding: 2px 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        font-size: 0.85rem;
        padding-left: 4px;
        line-height: 30px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        right: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }
    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        overflow: hidden;
        margin-top: 4px;
        z-index: 9999;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.85rem;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        outline: none;
    }
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #4361ee !important;
        border-radius: 6px;
    }
    .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.85rem;
    }
    .select2-container {
        width: 100% !important;
    }
    .btn-white {
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .btn-white:hover {
        background: #f8fafc;
    }
    .table-transparent {
        background: transparent !important;
    }
    .table-transparent th {
        background: #f8fafc !important;
        border-bottom: none !important;
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.75rem 1rem;
    }
    .table-transparent td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-transparent tr:last-child td {
        border-bottom: none;
    }
</style>

<div id="explore" class="container-fluid px-0">
    <!-- Compact Search & Filter Panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: #ffffff;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                        <i class="fas fa-building text-primary me-2" style="font-size: 0.85rem;"></i>
                        <select name="building" class="filter-select" data-placeholder="อาคาร..." onchange="this.form.submit()">
                            <option value=""></option>
                            <?php foreach ($all_buildings as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" <?= $f_building === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                        <i class="fas fa-layer-group text-primary me-2" style="font-size: 0.85rem;"></i>
                        <select name="floor" class="filter-select" data-placeholder="ชั้น..." onchange="this.form.submit()">
                            <option value=""></option>
                            <?php foreach ($all_floors as $f): ?>
                                <option value="<?= htmlspecialchars($f) ?>" <?= $f_floor === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                        <i class="fas fa-users text-primary me-2" style="font-size: 0.85rem;"></i>
                        <select name="dept" class="filter-select" data-placeholder="แผนก..." onchange="this.form.submit()">
                            <option value=""></option>
                            <?php foreach ($all_depts as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>" <?= $f_dept === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold shadow-sm">กรอง</button>
                    <a href="index.php" class="btn btn-outline-danger btn-sm rounded-3 px-2"><i class="fas fa-rotate-left"></i> ล้าง</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 4 Stats Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Card 1: สินค้าทั้งหมด -->
        <div class="col-sm-6 col-lg-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pages/dashboard_details.php?type=all" class="text-decoration-none text-dark d-block h-100">
            <?php else: ?>
                <a href="javascript:void(0)" class="text-decoration-none text-dark d-block h-100" data-bs-toggle="modal" data-bs-target="#loginModal">
            <?php endif; ?>
                <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-muted small fw-bold">สินค้าทั้งหมด</span>
                            <h3 class="fw-bold mb-0 text-dark mt-1"><?= number_format($total_products) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                            <div class="text-primary small fw-bold mt-1" style="font-size: 0.75rem;">มูลค่ารวม ฿<?= number_format($total_value, 2) ?></div>
                        </div>
                        <div class="stat-icon-container bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-start" style="min-height: 42px;">
                        <span class="trend-badge bg-primary bg-opacity-10 text-primary text-nowrap">
                            <i class="fas fa-list-ul"></i> ยอดรวม
                        </span>
                        <span class="text-muted small ms-2 lh-sm">สินค้าทุกประเภทในระบบ</span>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card 2: พร้อมใช้งาน -->
        <div class="col-sm-6 col-lg-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pages/dashboard_details.php?type=available" class="text-decoration-none text-dark d-block h-100">
            <?php else: ?>
                <a href="javascript:void(0)" class="text-decoration-none text-dark d-block h-100" data-bs-toggle="modal" data-bs-target="#loginModal">
            <?php endif; ?>
                <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-muted small fw-bold">พร้อมใช้งาน</span>
                            <h3 class="fw-bold mb-0 text-success mt-1"><?= number_format($total_available) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        </div>
                        <div class="stat-icon-container bg-success bg-opacity-10 text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-start" style="min-height: 42px;">
                        <span class="trend-badge bg-success bg-opacity-10 text-success text-nowrap">
                            <i class="fas fa-clipboard-check"></i> พร้อมใช้
                        </span>
                        <span class="text-muted small ms-2 lh-sm">สต็อกปัจจุบันที่เบิกได้</span>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card 3: ถูกเบิกใช้งาน -->
        <div class="col-sm-6 col-lg-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pages/dashboard_details.php?type=borrowed" class="text-decoration-none text-dark d-block h-100">
            <?php else: ?>
                <a href="javascript:void(0)" class="text-decoration-none text-dark d-block h-100" data-bs-toggle="modal" data-bs-target="#loginModal">
            <?php endif; ?>
                <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-muted small fw-bold">ถูกเบิกใช้งาน</span>
                            <h3 class="fw-bold mb-0 text-warning mt-1"><?= number_format($total_borrowed) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        </div>
                        <div class="stat-icon-container bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-start" style="min-height: 42px;">
                        <span class="trend-badge text-nowrap" style="background-color: #fef3c7; color: #b45309;">
                            <i class="fas fa-people-carry-box"></i> ถูกเบิก
                        </span>
                        <span class="text-muted small ms-2 lh-sm">อยู่ระหว่างการใช้งาน</span>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Card 4: สินค้าใกล้หมด -->
        <div class="col-sm-6 col-lg-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="pages/dashboard_details.php?type=low_stock" class="text-decoration-none text-dark d-block h-100">
            <?php else: ?>
                <a href="javascript:void(0)" class="text-decoration-none text-dark d-block h-100" data-bs-toggle="modal" data-bs-target="#loginModal">
            <?php endif; ?>
                <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-muted small fw-bold">สินค้าใกล้หมด</span>
                            <h3 class="fw-bold mb-0 text-danger mt-1"><?= number_format($low_stock) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        </div>
                        <div class="stat-icon-container bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-start" style="min-height: 42px;">
                        <span class="trend-badge trend-down text-nowrap">
                            <i class="fas fa-triangle-exclamation"></i> ตรวจสอบสต็อก
                        </span>
                        <span class="text-muted small ms-2 lh-sm">ต่ำกว่าเกณฑ์แจ้งเตือน</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-line text-primary me-2"></i>สถิติการเบิกรายเดือน</h6>
                </div>
                <div class="flex-grow-1" style="position: relative; min-height: 250px;">
                    <canvas id="borrowChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card">
                <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i>สัดส่วนสถานะสินค้า</h6>
                <div class="row align-items-center flex-grow-1">
                    <div class="col-12 position-relative d-flex justify-content-center align-items-center">
                        <div style="position: relative; width: 100%; max-width: 250px; aspect-ratio: 1;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card p-0 overflow-hidden">
                <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i>รายการเบิกล่าสุด</h6>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="pages/search.php" class="btn btn-white btn-sm rounded-pill px-3">ดูทั้งหมด</a>
                    <?php else: ?>
                        <a href="javascript:void(0)" class="btn btn-white btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#loginModal">ดูทั้งหมด</a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-transparent align-middle text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>สินค้า</th>
                                <th>Serial</th>
                                <th>ราคาต่อหน่วย</th>
                                <th>ผู้เบิก</th>
                                <th>วันที่เบิก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_recent = "SELECT b.*, ps.serial_code, p.name as p_name, p.price, CONCAT(u.firstname, ' ', u.lastname) as u_name 
                                         FROM borrowings b 
                                         JOIN product_serials ps ON b.serial_id = ps.id 
                                         JOIN products p ON ps.product_id = p.id 
                                         JOIN users u ON b.borrower_id = u.id 
                                         $where_sql
                                         ORDER BY b.borrowed_at DESC LIMIT 5";
                            $stmt_recent = $pdo->prepare($sql_recent);
                            $stmt_recent->execute($params);
                            $recent = $stmt_recent->fetchAll();

                            if (empty($recent)) {
                                echo '<tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลการเบิก</td></tr>';
                            }
                            foreach ($recent as $row) {
                                echo "<tr>
                                        <td><div class='fw-bold text-dark'>{$row['p_name']}</div></td>
                                        <td><code class='bg-light px-2 py-1 rounded text-dark'>{$row['serial_code']}</code></td>
                                        <td class='text-primary fw-bold'>฿" . number_format($row['price'], 2) . "</td>
                                        <td><i class='fas fa-user-circle text-muted me-1'></i> {$row['u_name']}</td>
                                        <td><span class='badge bg-light text-dark border fw-normal'>" . date('d/m/Y H:i', strtotime($row['borrowed_at'])) . "</span></td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.filter-select').select2({
        allowClear: true,
        placeholder: function() { return $(this).data('placeholder'); },
        language: {
            noResults: function() { return 'ไม่พบข้อมูล'; },
            searching: function() { return 'กำลังค้นหา...'; }
        }
    });

    // Borrow Chart
    const ctxBorrow = document.getElementById('borrowChart').getContext('2d');
    new Chart(ctxBorrow, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'จำนวนครั้งที่เบิก',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: '#4361ee',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'เบิก: ' + context.raw + ' ครั้ง';
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1 },
                    grid: { color: 'rgba(0,0,0,0.05)' } 
                },
                x: { grid: { display: false } }
            }
        }
    });

    // Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['พร้อมใช้', 'ถูกเบิก', 'อื่นๆ/ชำรุด'],
            datasets: [{
                data: [<?= $total_available_all ?>, <?= $total_borrowed_all ?>, <?= $total_other ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
});
</script>
"""

new_content = content[:start_idx] + new_html + content[end_idx:]

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Patch applied successfully.")
