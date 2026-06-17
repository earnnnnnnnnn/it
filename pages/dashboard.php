<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$page_title = 'Dashboard';

// Filters
$f_building = $_GET['building'] ?? '';
$f_floor = $_GET['floor'] ?? '';
$f_dept = $_GET['dept'] ?? '';

$where_clauses = [];
$params = [];

if ($f_building) {
    $where_clauses[] = "building = ?";
    $params[] = $f_building;
}
if ($f_floor) {
    $where_clauses[] = "floor = ?";
    $params[] = $f_floor;
}
if ($f_dept) {
    $where_clauses[] = "department = ?";
    $params[] = $f_dept;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch stats (Filtered by location if selected)
if ($where_sql) {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT serial_id) FROM borrowings $where_sql");
    $stmt->execute($params);
    $total_borrowed = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $where_sql");
    $stmt->execute($params);
    $total_products = $stmt->fetchColumn();

    $total_available = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE status = 'available'")->fetchColumn();
    $low_stock = $pdo->query("SELECT COUNT(*) FROM (SELECT p.id FROM products p LEFT JOIN product_serials ps ON p.id = ps.product_id AND ps.status = 'available' GROUP BY p.id, p.min_alert HAVING COUNT(ps.id) <= p.min_alert) as low_stock_items")->fetchColumn();
} else {
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_available = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE status = 'available'")->fetchColumn();
    $total_borrowed = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE status = 'borrowed'")->fetchColumn();
    $low_stock = $pdo->query("SELECT COUNT(*) FROM (SELECT p.id FROM products p LEFT JOIN product_serials ps ON p.id = ps.product_id AND ps.status = 'available' GROUP BY p.id, p.min_alert HAVING COUNT(ps.id) <= p.min_alert) as low_stock_items")->fetchColumn();
}

$total_value = $pdo->query("SELECT SUM(p.price) FROM product_serials ps JOIN products p ON ps.product_id = p.id")->fetchColumn();

// Fetch filter options for sidebar/dropdowns
function getFilteredStats($pdo, $column, $where_sql, $params) {
    $sql = "SELECT $column as label, COUNT(*) as count FROM borrowings $where_sql " . ($where_sql ? " AND " : " WHERE ") . " $column IS NOT NULL AND $column != '' GROUP BY $column ORDER BY count DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$building_stats = getFilteredStats($pdo, 'building', $where_sql, $params);
$floor_stats = getFilteredStats($pdo, 'floor', $where_sql, $params);
$dept_stats = getFilteredStats($pdo, 'department', $where_sql, $params);

// Fetch ALL options for the searchable dropdowns
$all_buildings = $pdo->query("SELECT DISTINCT building FROM borrowings WHERE building != '' ORDER BY building ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_floors = $pdo->query("SELECT DISTINCT floor FROM borrowings WHERE floor != '' ORDER BY floor ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_depts = $pdo->query("SELECT DISTINCT department FROM borrowings WHERE department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch Monthly Stats for Chart (Current Year)
$monthly_sql = "SELECT MONTH(borrowed_at) as month, COUNT(*) as count 
                FROM borrowings 
                $where_sql 
                " . ($where_sql ? " AND " : " WHERE ") . " YEAR(borrowed_at) = YEAR(CURDATE())
                GROUP BY MONTH(borrowed_at) 
                ORDER BY month ASC";
$stmt_monthly = $pdo->prepare($monthly_sql);
$stmt_monthly->execute($params);
$monthly_results = $stmt_monthly->fetchAll(PDO::FETCH_KEY_PAIR);

$months_th = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$chart_labels = [];
$chart_data = [];
for ($i = 1; $i <= 12; $i++) {
    $chart_labels[] = $months_th[$i-1];
    $chart_data[] = $monthly_results[$i] ?? 0;
}

// Fetch Status Stats for Pie Chart
$status_counts = $pdo->query("SELECT status, COUNT(*) as count FROM product_serials GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_available_all = $status_counts['available'] ?? 0;
$total_borrowed_all = $status_counts['borrowed'] ?? 0;
$total_other = ($status_counts['repairing'] ?? 0) + ($status_counts['broken'] ?? 0) + ($status_counts['lost'] ?? 0);

require_once '../includes/header.php';
?>

<div class="row g-4 mb-4">
    <!-- 1. Search Bar (ONLY Search Bar at the Top) -->
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 15px; background: #fff;">
            <div class="card-body p-3">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm border rounded-pill px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="building" class="form-control bg-transparent border-0" list="listBuildings" placeholder="ค้นหาอาคาร..." value="<?= htmlspecialchars($f_building) ?>" onchange="this.form.submit()">
                            <datalist id="listBuildings">
                                <?php foreach ($all_buildings as $b): ?>
                                    <option value="<?= htmlspecialchars($b) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm border rounded-pill px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-layer-group"></i></span>
                            <input type="text" name="floor" class="form-control bg-transparent border-0" list="listFloors" placeholder="ค้นหาชั้น..." value="<?= htmlspecialchars($f_floor) ?>" onchange="this.form.submit()">
                            <datalist id="listFloors">
                                <?php foreach ($all_floors as $f): ?>
                                    <option value="<?= htmlspecialchars($f) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm border rounded-pill px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-users-gear"></i></span>
                            <input type="text" name="dept" class="form-control bg-transparent border-0" list="listDepts" placeholder="ค้นหาแผนก..." value="<?= htmlspecialchars($f_dept) ?>" onchange="this.form.submit()">
                            <datalist id="listDepts">
                                <?php foreach ($all_depts as $d): ?>
                                    <option value="<?= htmlspecialchars($d) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">ค้นหา</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-1"><i class="fas fa-sync-alt"></i></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Stats Cards -->
    <div class="col-6 col-lg-3">
        <a href="dashboard_details.php?type=all" class="card h-100 border-0 shadow-sm card-hover text-decoration-none" style="border-radius: 12px;">
            <div class="stat-card p-3">
                <div>
                    <div class="text-muted small fw-bold">สินค้าทั้งหมด</div>
                    <div class="fs-2 fw-bold text-dark"><?= number_format($total_products) ?></div>
                    <div class="text-primary small fw-bold">มูลค่ารวม ฿<?= number_format($total_value, 2) ?></div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-boxes fs-4"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="dashboard_details.php?type=available" class="card h-100 border-0 shadow-sm card-hover text-decoration-none" style="border-radius: 12px;">
            <div class="stat-card p-3">
                <div>
                    <div class="text-muted small fw-bold">พร้อมใช้งาน</div>
                    <div class="fs-2 fw-bold text-success"><?= number_format($total_available) ?></div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-check-circle fs-4"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="dashboard_details.php?type=borrowed" class="card h-100 border-0 shadow-sm card-hover text-decoration-none" style="border-radius: 12px;">
            <div class="stat-card p-3">
                <div>
                    <div class="text-muted small fw-bold">ถูกเบิกใช้งาน</div>
                    <div class="fs-2 fw-bold text-warning"><?= number_format($total_borrowed) ?></div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-hand-holding-heart fs-4"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="dashboard_details.php?type=low_stock" class="card h-100 border-0 shadow-sm card-hover text-decoration-none" style="border-radius: 12px;">
            <div class="stat-card p-3">
                <div>
                    <div class="text-muted small fw-bold">สินค้าใกล้หมด</div>
                    <div class="fs-2 fw-bold text-danger"><?= number_format($low_stock) ?></div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-triangle-exclamation fs-4"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Main Charts -->
    <div class="col-lg-8">
        <div class="card p-4 border-0 shadow-sm" style="border-radius: 15px;">
            <h6 class="fw-bold mb-4"><i class="fas fa-chart-line text-primary me-2"></i>สถิติการเบิกรายเดือน</h6>
            <div style="position: relative; height: 300px;">
                <canvas id="borrowChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 border-0 shadow-sm" style="border-radius: 15px;">
            <h6 class="fw-bold mb-4"><i class="fas fa-chart-pie text-primary me-2"></i>สัดส่วนสถานะสินค้า</h6>
            <div style="position: relative; height: 300px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 4. Location Intelligence -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 py-3 mt-2">
                <div class="d-flex justify-content-between align-items-center px-2">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-map-location-dot text-primary me-2"></i> ข้อมูลพื้นที่และการใช้งาน
                    </h5>
                    <?php if ($f_building || $f_floor || $f_dept): ?>
                        <a href="dashboard.php" class="btn btn-sm btn-danger rounded-pill px-3">
                            <i class="fas fa-rotate-left me-1"></i> ล้างการเลือก
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Left: Building Navigator -->
                    <div class="col-lg-3 border-end bg-light bg-opacity-25" style="max-height: 400px; overflow-y: auto;">
                        <div class="p-3">
                            <div class="text-muted small fw-bold mb-3 text-uppercase tracking-wider">อาคารทั้งหมด</div>
                            <div class="d-flex flex-column gap-1">
                                <a href="dashboard.php" class="text-decoration-none p-2 rounded-3 d-flex justify-content-between align-items-center <?= !$f_building ? 'bg-primary text-white shadow-sm' : 'text-dark hover-bg-light' ?>">
                                    <span><i class="fas fa-hospital-user me-2"></i> ทุกอาคาร</span>
                                </a>
                                <?php 
                                $buildings_sidebar = $pdo->query("SELECT building, COUNT(*) as count FROM borrowings WHERE building != '' GROUP BY building ORDER BY count DESC")->fetchAll();
                                foreach ($buildings_sidebar as $bs): 
                                ?>
                                    <a href="?building=<?= urlencode($bs['building']) ?>" class="text-decoration-none p-2 rounded-3 d-flex justify-content-between align-items-center <?= $f_building == $bs['building'] ? 'bg-primary text-white shadow-sm' : 'text-dark hover-bg-light' ?>">
                                        <span class="text-truncate small me-2"><i class="fas fa-building me-2"></i> <?= htmlspecialchars($bs['building']) ?></span>
                                        <span class="badge rounded-pill <?= $f_building == $bs['building'] ? 'bg-white text-primary' : 'bg-secondary bg-opacity-10 text-muted' ?>" style="font-size: 0.7rem;"><?= $bs['count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Detailed Insights -->
                    <div class="col-lg-9 bg-white">
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-4 d-flex align-items-center">
                                        <span class="bg-info bg-opacity-10 text-info p-2 rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-layer-group small"></i></span>
                                        การกระจายตามชั้น
                                    </h6>
                                    <div class="d-flex flex-column gap-3">
                                        <?php 
                                        $max_floor = !empty($floor_stats) ? max(array_column($floor_stats, 'count')) : 1;
                                        foreach ($floor_stats as $fs): 
                                            $percent = ($fs['count'] / $max_floor) * 100;
                                            $is_floor_active = ($f_floor == $fs['label']);
                                            $floor_url_params = $_GET;
                                            if ($is_floor_active) unset($floor_url_params['floor']); else $floor_url_params['floor'] = $fs['label'];
                                            $floor_url = "?" . http_build_query($floor_url_params);
                                        ?>
                                            <div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <a href="<?= $floor_url ?>" class="text-decoration-none small fw-bold <?= $is_floor_active ? 'text-primary' : 'text-dark' ?> hover-text-primary">
                                                        <?= $is_floor_active ? '<i class="fas fa-check-circle me-1"></i>' : '' ?>
                                                        <?= htmlspecialchars($fs['label']) ?>
                                                    </a>
                                                    <span class="small text-muted"><?= $fs['count'] ?> รายการ</span>
                                                </div>
                                                <div class="progress" style="height: 8px; border-radius: 4px;">
                                                    <div class="progress-bar <?= $is_floor_active ? 'bg-primary shadow-sm' : 'bg-info' ?>" role="progressbar" style="width: <?= $percent ?>%" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; if(empty($floor_stats)) echo '<div class="text-center py-4 text-muted small">ไม่มีข้อมูล</div>'; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-4 d-flex align-items-center">
                                        <span class="bg-success bg-opacity-10 text-success p-2 rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-users-viewfinder small"></i></span>
                                        แผนกที่ใช้งานสูงสุด
                                    </h6>
                                    <div class="row g-2">
                                        <?php foreach ($dept_stats as $idx => $ds): 
                                            $is_dept_active = ($f_dept == $ds['label']);
                                            $dept_url_params = $_GET;
                                            if ($is_dept_active) unset($dept_url_params['dept']); else $dept_url_params['dept'] = $ds['label'];
                                            $dept_url = "?" . http_build_query($dept_url_params);
                                        ?>
                                            <div class="col-6">
                                                <a href="<?= $dept_url ?>" class="text-decoration-none d-flex align-items-center p-2 rounded-3 border <?= $is_dept_active ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light-subtle bg-light bg-opacity-50' ?> hover-shadow-sm transition-all">
                                                    <div class="me-2 <?= $is_dept_active ? 'bg-primary text-white' : 'bg-white text-primary' ?> shadow-sm rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 24px; height: 24px; font-size: 10px;">
                                                        <?= $is_dept_active ? '<i class="fas fa-check"></i>' : ($idx + 1) ?>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.75rem;"><?= htmlspecialchars($ds['label']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.65rem;"><?= $ds['count'] ?> ครั้ง</div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; if(empty($dept_stats)) echo '<div class="col-12 text-center py-4 text-muted small">ไม่มีข้อมูล</div>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Recent Transactions -->
    <div class="col-md-12">
        <div class="card p-4 border-0 shadow-sm" style="border-radius: 15px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>รายการเบิกล่าสุด</h6>
                <a href="reports.php" class="btn btn-sm btn-light rounded-pill px-3">ดูทั้งหมด</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">สินค้า</th>
                            <th class="border-0">Serial</th>
                            <th class="border-0" style="white-space: nowrap;">ราคา<br><span class="text-muted small fw-normal">ราคาเช่า</span></th>
                            <th class="border-0">เลขครุภัณฑ์</th>
                            <th class="border-0">ผู้เบิก</th>
                            <th class="border-0">วันที่เบิก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_recent = "SELECT b.*, ps.serial_code, p.name as p_name, p.price, p.rental_price, CONCAT(u.firstname, ' ', u.lastname) as u_name 
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
                            echo '<tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีข้อมูลการเบิก</td></tr>';
                        }
                        foreach ($recent as $row) {
                            echo "<tr>
                                    <td><div class='fw-bold text-dark'>{$row['p_name']}</div></td>
                                    <td><code>{$row['serial_code']}</code></td>
                                    <td>
                                        <div class='text-primary fw-bold mb-1'>฿" . number_format($row['price'], 2) . "</div>\" . 
                                        (($row['rental_price'] ?? 0) > 0 ? \"<div class='text-success fw-bold' style='font-size: 0.85rem;'>เช่า: ฿\" . number_format($row['rental_price'], 2) . \"</div>\" : \"\") . \"
                                    </td>
                                    <td><span class='text-muted small'>{$row['asset_number']}</span></td>
                                    <td><i class='fas fa-user-circle text-muted me-1'></i> {$row['u_name']}</td>
                                    <td><span class='badge bg-light text-dark fw-normal'>" . date('d/m/Y H:i', strtotime($row['borrowed_at'])) . "</span></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
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
                backgroundColor: ['#4cc9f0', '#4361ee', '#ff4d4d'],
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
</script>

<style>
    .hover-bg-light:hover { background-color: rgba(0,0,0,0.05); transition: 0.2s; }
    .transition-all { transition: all 0.2s ease-in-out; }
    .hover-shadow-sm:hover { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; transform: translateY(-1px); }
</style>

<?php require_once '../includes/footer.php'; ?>
