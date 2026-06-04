<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

// Authentication Check: Only logged in users can access the administrative dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_root = true;
$page_title = 'Dashboard';

function formatThaiDate($dateStr) {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $day = date('j', $time);
    $month = $thai_months[(int)date('n', $time)];
    $year = (int)date('Y', $time) + 543;
    return "$day $month $year";
}

// Filters (from the search/filter panel)
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

$f_period = $_GET['period'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$period_sql = "";
$period_label = "วันนี้";

if ($start_date && $end_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $period_sql = "DATE(b.borrowed_at) BETWEEN '$start_date' AND '$end_date'";
    $period_label = formatThaiDate($start_date) . " - " . formatThaiDate($end_date);
    $f_period = ''; // clear period if custom dates are used
} elseif ($start_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $period_sql = "DATE(b.borrowed_at) >= '$start_date'";
    $period_label = "ตั้งแต่ " . formatThaiDate($start_date);
    $f_period = '';
} elseif ($end_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $period_sql = "DATE(b.borrowed_at) <= '$end_date'";
    $period_label = "ถึง " . formatThaiDate($end_date);
    $f_period = '';
} elseif ($f_period === 'week') {
    $period_sql = "YEARWEEK(b.borrowed_at, 1) = YEARWEEK(CURDATE(), 1)";
    $period_label = "สัปดาห์นี้";
} elseif ($f_period === 'month') {
    $period_sql = "YEAR(b.borrowed_at) = YEAR(CURDATE()) AND MONTH(b.borrowed_at) = MONTH(CURDATE())";
    $period_label = "เดือนนี้";
} elseif ($f_period === 'year') {
    $period_sql = "YEAR(b.borrowed_at) = YEAR(CURDATE())";
    $period_label = "ปีนี้";
} elseif ($f_period === 'today') {
    $period_sql = "DATE(b.borrowed_at) = CURDATE()";
    $period_label = "วันนี้";
} else {
    $period_sql = "";
    $period_label = "ทั้งหมด";
}

$borrow_where_clauses = $where_clauses;
if ($period_sql) {
    $borrow_where_clauses[] = $period_sql;
}
$borrow_where_sql = "WHERE " . implode(" AND ", $borrow_where_clauses);
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$chart_where_clauses = $where_clauses;
if ($period_sql) {
    $chart_where_clauses[] = $period_sql;
}
$chart_where_sql = !empty($chart_where_clauses) ? "WHERE " . implode(" AND ", $chart_where_clauses) : "";

// --- Core Stats ---
$total_products_raw = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_serials_raw = $pdo->query("SELECT COUNT(*) FROM product_serials")->fetchColumn();

// Stats for ALL currently borrowed items (period filter applied to show items borrowed in the date range that are still in use)
$borrowed_all_clauses = $borrow_where_clauses;
$borrowed_all_clauses[] = "b.returned_at IS NULL";
$borrowed_all_where = "WHERE " . implode(" AND ", $borrowed_all_clauses);

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT ps.product_id) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id $borrowed_all_where");
$stmt->execute($params);
$total_products = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT b.serial_id) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id $borrowed_all_where");
$stmt->execute($params);
$total_serials = $stmt->fetchColumn();

$total_borrowed = $total_serials;

$stmt = $pdo->prepare("SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $borrowed_all_where");
$stmt->execute($params);
$total_borrowed_val = $stmt->fetchColumn() ?? 0;
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_available = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE status = 'available'")->fetchColumn();
$total_available_value = $pdo->query("SELECT SUM(p.price) FROM product_serials ps JOIN products p ON ps.product_id = p.id WHERE ps.status = 'available'")->fetchColumn() ?? 0;
$low_stock = $pdo->query("SELECT COUNT(*) FROM (SELECT p.id FROM products p LEFT JOIN product_serials ps ON p.id = ps.product_id AND ps.status = 'available' GROUP BY p.id, p.min_alert HAVING COUNT(ps.id) <= p.min_alert) as low_stock_items")->fetchColumn();
$low_stock_value = $pdo->query("SELECT SUM(p.price) FROM products p WHERE p.id IN (SELECT p2.id FROM products p2 LEFT JOIN product_serials ps ON p2.id = ps.product_id AND ps.status = 'available' GROUP BY p2.id, p2.min_alert HAVING COUNT(ps.id) <= p2.min_alert)")->fetchColumn() ?? 0;
$total_broken = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE status IN ('broken', 'lost', 'repairing')")->fetchColumn();
$total_value = $pdo->query("SELECT SUM(p.price) FROM product_serials ps JOIN products p ON ps.product_id = p.id")->fetchColumn() ?? 0;

// --- Month-over-Month Trends & Fallbacks ---
function getTrendPercentage($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return (($current - $previous) / $previous) * 100;
}

$cur_serials = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
$prev_serials = $pdo->query("SELECT COUNT(*) FROM product_serials WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND created_at < DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
$serials_trend = getTrendPercentage($cur_serials, $prev_serials);


// Monthly trend filtered if needed
if ($where_sql) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings b WHERE b.borrowed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01') AND " . implode(" AND ", $where_clauses));
    $stmt->execute($params);
    $cur_borrowed = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings b WHERE b.borrowed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND b.borrowed_at < DATE_FORMAT(NOW() ,'%Y-%m-01') AND " . implode(" AND ", $where_clauses));
    $stmt->execute($params);
    $prev_borrowed = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id WHERE b.borrowed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01') AND " . implode(" AND ", $where_clauses));
    $stmt->execute($params);
    $cur_val = $stmt->fetchColumn() ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id WHERE b.borrowed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND b.borrowed_at < DATE_FORMAT(NOW() ,'%Y-%m-01') AND " . implode(" AND ", $where_clauses));
    $stmt->execute($params);
    $prev_val = $stmt->fetchColumn() ?? 0;
} else {
    $cur_borrowed = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE borrowed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
    $prev_borrowed = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE borrowed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND borrowed_at < DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
    
    $cur_val = $pdo->query("SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id WHERE b.borrowed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn() ?? 0;
    $prev_val = $pdo->query("SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id WHERE b.borrowed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND b.borrowed_at < DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn() ?? 0;
}

$borrowed_trend = getTrendPercentage($cur_borrowed, $prev_borrowed);


$val_trend = getTrendPercentage($cur_val, $prev_val);


$cur_users = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
$prev_users = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH) ,'%Y-%m-01') AND created_at < DATE_FORMAT(NOW() ,'%Y-%m-01')")->fetchColumn();
$users_trend = $cur_users - $prev_users;


// --- Datalist search options ---
$all_buildings = $pdo->query("SELECT name FROM buildings ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_floors = $pdo->query("SELECT name FROM floors ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_depts = $pdo->query("SELECT name FROM departments ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);

// --- Chart Mode Selection (Daily/Monthly) ---
$chart_mode = $_GET['chart_mode'] ?? 'monthly';
$months_th = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

$chart_labels = [];
$chart_data = [];

if ($chart_mode === 'daily') {
    $daily_sql = "SELECT DAY(borrowed_at) as day, COUNT(*) as count 
                  FROM borrowings b 
                  " . ($chart_where_sql ? "$chart_where_sql AND" : "WHERE") . " MONTH(b.borrowed_at) = MONTH(CURDATE()) AND YEAR(b.borrowed_at) = YEAR(CURDATE())
                  GROUP BY DAY(b.borrowed_at) 
                  ORDER BY day ASC";
    $stmt_daily = $pdo->prepare($daily_sql);
    $stmt_daily->execute($params);
    $daily_results = $stmt_daily->fetchAll(PDO::FETCH_KEY_PAIR);

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('n'), date('Y'));
    $month_name = $months_th[date('n')-1];
    for ($i = 1; $i <= $days_in_month; $i++) {
        $chart_labels[] = $i . ' ' . $month_name;
        $chart_data[] = $daily_results[$i] ?? 0;
    }
} else {
    // --- Monthly Borrowing counts (for Line Chart) ---
    $monthly_sql = "SELECT MONTH(borrowed_at) as month, COUNT(*) as count 
                    FROM borrowings b 
                    " . ($chart_where_sql ? "$chart_where_sql AND" : "WHERE") . " YEAR(b.borrowed_at) = YEAR(CURDATE())
                    GROUP BY MONTH(b.borrowed_at) 
                    ORDER BY month ASC";
    $stmt_monthly = $pdo->prepare($monthly_sql);
    $stmt_monthly->execute($params);
    $monthly_results = $stmt_monthly->fetchAll(PDO::FETCH_KEY_PAIR);

    for ($i = 1; $i <= 12; $i++) {
        $chart_labels[] = $months_th[$i-1] . ' ' . substr(date('Y') + 543, 2);
        $chart_data[] = $monthly_results[$i] ?? 0;
    }
}



// --- Top Categories (Doughnut Chart) ---
$cat_sql = "SELECT p.category, COUNT(*) as count 
            FROM borrowings b 
            JOIN product_serials ps ON b.serial_id = ps.id 
            JOIN products p ON ps.product_id = p.id 
            " . $chart_where_sql . "
            GROUP BY p.category 
            ORDER BY count DESC 
            LIMIT 5";
$stmt_cat = $pdo->prepare($cat_sql);
$stmt_cat->execute($params);
$categories_data = $stmt_cat->fetchAll();


$cat_sum = array_sum(array_column($categories_data, 'count'));

// --- Top Locations (Building-Floor-Department) ---
$loc_sql = "SELECT building, floor, department, COUNT(*) as count, SUM(p.price) as total_val 
            FROM borrowings b 
            JOIN product_serials ps ON b.serial_id = ps.id 
            JOIN products p ON ps.product_id = p.id 
            " . $chart_where_sql . "
            GROUP BY building, floor, department 
            ORDER BY count DESC 
            LIMIT 5";
$stmt_loc = $pdo->prepare($loc_sql);
$stmt_loc->execute($params);
$locations_data = $stmt_loc->fetchAll();



// --- Recent Transactions ---
$recent_where = $chart_where_sql;
if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    $recent_where .= ($recent_where ? " AND " : "WHERE ") . "b.borrower_id = " . (int)$_SESSION['user_id'];
}
$recent_sql = "SELECT b.*, ps.serial_code, p.name as p_name, p.brand, p.model, p.price, p.image as p_image, CONCAT(u.firstname, ' ', u.lastname) as u_name, u.image as u_image 
               FROM borrowings b 
               JOIN product_serials ps ON b.serial_id = ps.id 
               JOIN products p ON ps.product_id = p.id 
               JOIN users u ON b.borrower_id = u.id 
               " . $recent_where . "
               ORDER BY b.borrowed_at DESC 
               LIMIT 5";
$stmt_recent = $pdo->prepare($recent_sql);
$stmt_recent->execute($params);
$recent_data = $stmt_recent->fetchAll();



// --- Department Value Distribution (Top 5 by Count) ---
$depts_sql = "SELECT department, COUNT(*) as count, SUM(p.price) as total_val 
              FROM borrowings b 
              JOIN product_serials ps ON b.serial_id = ps.id 
              JOIN products p ON ps.product_id = p.id 
              " . $chart_where_sql . "
              GROUP BY department 
              ORDER BY count DESC 
              LIMIT 5";
$stmt_depts = $pdo->prepare($depts_sql);
$stmt_depts->execute($params);
$depts_data = $stmt_depts->fetchAll();

// --- Top Products (Most Borrowed) ---
$top_products_sql = "SELECT p.name, p.image, COUNT(*) as count 
                     FROM borrowings b 
                     JOIN product_serials ps ON b.serial_id = ps.id 
                     JOIN products p ON ps.product_id = p.id 
                     " . $chart_where_sql . "
                     GROUP BY p.id, p.name, p.image 
                     ORDER BY count DESC 
                     LIMIT 5";
$stmt_top_prod = $pdo->prepare($top_products_sql);
$stmt_top_prod->execute($params);
$top_products_data = $stmt_top_prod->fetchAll();


$dept_max_val = !empty($depts_data) ? max(array_column($depts_data, 'total_val')) : 1;

// --- Monthly Overview Statistics ---
$monthly_detail_where = ($chart_where_sql ? "$chart_where_sql AND" : "WHERE") . " MONTH(b.borrowed_at) = MONTH(CURRENT_DATE()) AND YEAR(b.borrowed_at) = YEAR(CURRENT_DATE())";

$month_count_sql = "SELECT COUNT(*) FROM borrowings b $monthly_detail_where";
$stmt_mc = $pdo->prepare($month_count_sql);
$stmt_mc->execute($params);
$month_count = $stmt_mc->fetchColumn();

$month_val_sql = "SELECT SUM(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $monthly_detail_where";
$stmt_mv = $pdo->prepare($month_val_sql);
$stmt_mv->execute($params);
$month_val = $stmt_mv->fetchColumn() ?? 0;

$month_max_sql = "SELECT MAX(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $monthly_detail_where";
$stmt_mx = $pdo->prepare($month_max_sql);
$stmt_mx->execute($params);
$month_max = $stmt_mx->fetchColumn() ?? 0;

$month_min_sql = "SELECT MIN(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $monthly_detail_where";
$stmt_mn = $pdo->prepare($month_min_sql);
$stmt_mn->execute($params);
$month_min = $stmt_mn->fetchColumn() ?? 0;

$month_avg_sql = "SELECT AVG(p.price) FROM borrowings b JOIN product_serials ps ON b.serial_id = ps.id JOIN products p ON ps.product_id = p.id $monthly_detail_where";
$stmt_ma = $pdo->prepare($month_avg_sql);
$stmt_ma->execute($params);
$month_avg = $stmt_ma->fetchColumn() ?? 0;



// --- Date Filter Setup (Thai Style) ---
$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$current_date_th = date('d') . ' ' . $thai_months[intval(date('m'))] . ' ' . (date('Y') + 543);

require_once 'includes/header.php';
?>
<!-- Select2 for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
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
</style>
<?php
?>

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
    .mini-stat-box {
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        padding: 0.75rem;
        text-align: center;
        transition: all 0.2s ease;
    }
    .mini-stat-box:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }
    .progress-bar-container {
        height: 6px;
        border-radius: 3px;
        background-color: #f1f5f9;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    /* Lighter date placeholder text (วว/ดด/ปปปป) when empty */
    input[type="date"]:invalid,
    input[type="date"][value=""] {
        color: #c0c7d0;
    }
    input[type="date"]::-webkit-datetime-edit-text,
    input[type="date"]::-webkit-datetime-edit-month-field,
    input[type="date"]::-webkit-datetime-edit-day-field,
    input[type="date"]::-webkit-datetime-edit-year-field {
        color: inherit;
    }
    input[type="date"]:not(:placeholder-shown):valid {
        color: #495057;
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



<!-- Compact Search & Filter Panel -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: #ffffff;">
    <div class="card-body p-3">
        <form method="GET" id="filterForm" class="row g-2 align-items-center">
            <input type="hidden" name="period" value="<?= htmlspecialchars($f_period) ?>">
            <div class="col-md-2">
                <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                    <i class="fas fa-building text-primary me-2" style="font-size: 0.85rem;"></i>
                    <select name="building" id="filterBuilding" class="filter-select" data-placeholder="อาคาร...">
                        <option value=""></option>
                        <?php foreach ($all_buildings as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= $f_building === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                    <i class="fas fa-layer-group text-primary me-2" style="font-size: 0.85rem;"></i>
                    <select name="floor" id="filterFloor" class="filter-select" data-placeholder="ชั้น...">
                        <option value=""></option>
                        <?php foreach ($all_floors as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $f_floor === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50" style="min-height: 38px;">
                    <i class="fas fa-users text-primary me-2" style="font-size: 0.85rem;"></i>
                    <select name="dept" id="filterDept" class="filter-select" data-placeholder="แผนก...">
                        <option value=""></option>
                        <?php foreach ($all_depts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $f_dept === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50 overflow-hidden" style="min-height: 38px;">
                    <input type="date" name="start_date" class="form-control border-0 bg-transparent p-1 shadow-none" style="font-size: 0.75rem;" value="<?= htmlspecialchars($start_date) ?>" title="ตั้งแต่วันที่">
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex align-items-center border rounded-3 px-2 bg-light bg-opacity-50 overflow-hidden" style="min-height: 38px;">
                    <input type="date" name="end_date" class="form-control border-0 bg-transparent p-1 shadow-none" style="font-size: 0.75rem;" value="<?= htmlspecialchars($end_date) ?>" title="ถึงวันที่">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold shadow-sm">กรอง</button>
                <a href="dashboard.php" class="btn btn-outline-danger btn-sm rounded-3 px-2"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
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
});
</script>

<!-- 4 Stats Cards Row -->
<div class="row g-3 mb-4">
    <!-- Card 1: สินค้าทั้งหมด -->
    <div class="col-sm-6 col-lg-3">
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        <a href="pages/dashboard_details.php?type=all" class="text-decoration-none text-dark d-block h-100">
        <?php else: ?>
        <div class="text-decoration-none text-dark d-block h-100" style="cursor: default;">
        <?php endif; ?>
            <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <span class="text-muted small fw-bold">สินค้าทั้งหมด</span>
                        <h3 class="fw-bold mb-0 text-dark mt-1"><?= number_format($total_products_raw) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
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
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Card 2: พร้อมใช้งาน -->
    <div class="col-sm-6 col-lg-3">
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        <a href="pages/dashboard_details.php?type=available" class="text-decoration-none text-dark d-block h-100">
        <?php else: ?>
        <div class="text-decoration-none text-dark d-block h-100" style="cursor: default;">
        <?php endif; ?>
            <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <span class="text-muted small fw-bold">พร้อมใช้งาน</span>
                        <h3 class="fw-bold mb-0 text-success mt-1"><?= number_format($total_available) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        <div class="text-success small fw-bold mt-1" style="font-size: 0.75rem;">มูลค่ารวม ฿<?= number_format($total_available_value, 2) ?></div>
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
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Card 3: ถูกเบิกใช้งาน -->
    <div class="col-sm-6 col-lg-3">
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        <a href="pages/dashboard_details.php?type=borrowed" class="text-decoration-none text-dark d-block h-100">
        <?php else: ?>
        <div class="text-decoration-none text-dark d-block h-100" style="cursor: default;">
        <?php endif; ?>
            <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <span class="text-muted small fw-bold">ถูกเบิกใช้งาน</span>
                        <h3 class="fw-bold mb-0 text-warning mt-1"><?= number_format($total_borrowed) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        <div class="small fw-bold mt-1" style="font-size: 0.75rem; color: #b45309;">มูลค่ารวม ฿<?= number_format($total_borrowed_val, 2) ?></div>
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
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Card 4: สินค้าใกล้หมด -->
    <div class="col-sm-6 col-lg-3">
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        <a href="pages/dashboard_details.php?type=low_stock" class="text-decoration-none text-dark d-block h-100">
        <?php else: ?>
        <div class="text-decoration-none text-dark d-block h-100" style="cursor: default;">
        <?php endif; ?>
            <div class="stat-card-new d-flex flex-column justify-content-between h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <span class="text-muted small fw-bold">สินค้าใกล้หมด</span>
                        <h3 class="fw-bold mb-0 text-danger mt-1"><?= number_format($low_stock) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h3>
                        <div class="text-danger small fw-bold mt-1" style="font-size: 0.75rem;">มูลค่ารวม ฿<?= number_format($low_stock_value, 2) ?></div>
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
        <?php if (in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Middle Row: Charts and Location stats -->
<div class="row g-4 mb-4">
    <!-- 1. สถิติการเบิกรายเดือน Line Chart -->
    <div class="col-lg-7">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-line text-primary me-2"></i>สถิติการเบิก (<?= $chart_mode === 'daily' ? 'รายวัน' : 'รายเดือน' ?>)</h6>
                <div class="dropdown">
                    <?php
                    $query_params = $_GET;
                    $query_params['chart_mode'] = 'daily';
                    $url_daily = '?' . http_build_query($query_params);
                    $query_params['chart_mode'] = 'monthly';
                    $url_monthly = '?' . http_build_query($query_params);
                    ?>
                    <button class="btn btn-white btn-sm border rounded-2 px-2 py-1 text-muted small dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?= $chart_mode === 'daily' ? 'รายวัน (เดือนนี้)' : 'รายเดือน (ปีนี้)' ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li><a class="dropdown-item <?= $chart_mode === 'daily' ? 'active' : '' ?> small" href="<?= htmlspecialchars($url_daily) ?>">รายวัน (เดือนนี้)</a></li>
                        <li><a class="dropdown-item <?= $chart_mode === 'monthly' ? 'active' : '' ?> small" href="<?= htmlspecialchars($url_monthly) ?>">รายเดือน (ปีนี้)</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex-grow-1" style="position: relative; min-height: 250px;">
                <canvas id="borrowChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- 2. หมวดหมู่สินค้าที่เบิกมากที่สุด Doughnut Chart -->
    <div class="col-lg-5">
        <div class="chart-card">
            <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i>หมวดหมู่สินค้าที่เบิกมากที่สุด</h6>
            <div class="row align-items-center flex-grow-1">
                <!-- Doughnut Canvas Column -->
                <div class="col-6 position-relative d-flex justify-content-center align-items-center">
                    <div style="position: relative; width: 100%; max-width: 150px; aspect-ratio: 1;">
                        <canvas id="categoryChart"></canvas>
                        <!-- Center Text inside Doughnut -->
                        <div class="position-absolute start-50 top-50 translate-middle text-center" style="pointer-events: none; transform: translate(-50%, -50%) !important;">
                            <div class="fw-bold fs-5 text-dark" style="line-height: 1.1; font-family: 'Outfit', sans-serif;"><?= number_format(count($categories_data)) ?></div>
                            <div class="text-muted" style="font-size: 0.65rem;">หมวดหมู่</div>
                        </div>
                    </div>
                </div>
                <!-- Categories Legend List Column -->
                <div class="col-6">
                    <div class="d-flex flex-column gap-2" style="font-size: 0.72rem;">
                        <?php 
                        $chart_colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#10b981'];
                        foreach ($categories_data as $index => $cat): 
                            $percentage = $cat_sum > 0 ? ($cat['count'] / $cat_sum) * 100 : 0;
                            $color = $chart_colors[$index % count($chart_colors)];
                        ?>
                            <div class="d-flex align-items-start gap-1">
                                <span class="rounded-circle mt-1" style="width: 8px; height: 8px; min-width: 8px; background-color: <?= $color ?>; display: inline-block;"></span>
                                <div class="overflow-hidden">
                                    <div class="text-dark fw-bold text-truncate" title="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></div>
                                    <div class="text-muted"><?= number_format($cat['count']) ?> รายการ</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Calculate building and floor distribution from $locations_data
$buildings_summary = [];
$floors_summary = [];

foreach ($locations_data as $loc) {
    $b = $loc['building'];
    $f = $loc['floor'];
    $c = $loc['count'];
    
    $buildings_summary[$b] = ($buildings_summary[$b] ?? 0) + $c;
    $floors_summary[$f] = ($floors_summary[$f] ?? 0) + $c;
}
arsort($buildings_summary);
arsort($floors_summary);
$max_floor_val = !empty($floors_summary) ? max($floors_summary) : 1;
?>

<!-- New Row: ข้อมูลพื้นที่และการใช้งาน -->
<div class="row mb-4">
    <div class="col-12">
        <div class="chart-card p-0 overflow-hidden">
            <div class="p-3 px-4 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-map-location-dot text-primary me-2"></i>ข้อมูลพื้นที่และการใช้งาน</h6>
            </div>
            
            <div class="row g-0">
                <!-- Column 1: อาคารทั้งหมด -->
                <div class="col-md-3 border-end bg-light bg-opacity-50 p-4">
                    <div class="text-muted small fw-bold mb-3">อาคารทั้งหมด</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex justify-content-between align-items-center fw-bold shadow-sm" style="font-size: 0.9rem;">
                            <span><i class="fas fa-building-user me-2"></i> ทุกอาคาร</span>
                        </div>
                        <?php foreach ($buildings_summary as $b_name => $b_count): ?>
                        <div class="p-2 d-flex justify-content-between align-items-center text-dark" style="font-size: 0.85rem;">
                            <span><i class="fas fa-building text-muted me-2"></i> <?= htmlspecialchars($b_name) ?></span>
                            <span class="badge bg-secondary bg-opacity-10 text-dark rounded-pill px-2"><?= number_format($b_count) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($buildings_summary)): ?>
                        <div class="p-2 text-center text-muted small">ไม่มีข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Column 2: การกระจายตามชั้น -->
                <div class="col-md-4 border-end p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="fas fa-layer-group" style="font-size: 0.7rem;"></i>
                        </div>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">การกระจายตามชั้น</span>
                    </div>
                    
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($floors_summary as $f_name => $f_count): 
                            $f_percent = ($f_count / $max_floor_val) * 100;
                        ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-dark fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($f_name) ?></span>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= number_format($f_count) ?> รายการ</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 3px; background-color: #f1f5f9;">
                                <div class="progress-bar bg-info" style="width: <?= $f_percent ?>%; border-radius: 3px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($floors_summary)): ?>
                        <div class="text-center text-muted small mt-4">ไม่มีข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Column 3: แผนกที่ใช้งานสูงสุด -->
                <div class="col-md-5 p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                            <i class="fas fa-users-viewfinder" style="font-size: 0.7rem;"></i>
                        </div>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">แผนกที่ใช้งานสูงสุด</span>
                    </div>
                    
                    <div class="row g-3">
                        <?php 
                        $rank = 1;
                        $top_depts = array_slice($depts_data, 0, 6);
                        foreach ($top_depts as $dept): ?>
                        <div class="col-sm-6">
                            <div class="border border-light-subtle rounded-3 p-2 d-flex align-items-center gap-2 h-100 bg-white shadow-sm" style="min-height: 50px;">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 20px; height: 20px; font-size: 0.65rem; min-width: 20px;">
                                    <?= $rank++ ?>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.75rem;" title="<?= htmlspecialchars($dept['department']) ?>">
                                        <?= htmlspecialchars($dept['department']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        <?= number_format($dept['count']) ?> ครั้ง
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($top_depts)): ?>
                        <div class="col-12 text-center text-muted small mt-4">ไม่มีข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top 5 Rankings Row -->
<div class="row g-4 mb-4">
    <!-- Top 5 Departments -->
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-medal text-primary me-2"></i>5 อันดับแผนกที่เบิกเยอะสุด</h6>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php 
                $rank = 1;
                foreach ($depts_data as $dept): ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light bg-opacity-50 border">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; min-width: 36px;">
                            #<?= $rank++ ?>
                        </div>
                        <div class="fw-bold text-dark text-truncate" style="max-width: 220px; font-size: 0.95rem;" title="<?= htmlspecialchars($dept['department']) ?>">
                            <?= htmlspecialchars($dept['department']) ?>
                        </div>
                    </div>
                    <div class="text-primary fw-bold bg-white px-3 py-1 rounded-pill shadow-sm border border-primary-subtle" style="font-size: 0.85rem;">
                        <?= number_format($dept['count']) ?> ครั้ง
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($depts_data)): ?>
                <div class="text-center text-muted py-3">ไม่มีข้อมูล</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top 5 Equipments -->
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-star text-warning me-2"></i>5 อันดับอุปกรณ์ที่ถูกเบิกบ่อยที่สุด</h6>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php 
                $rank = 1;
                foreach ($top_products_data as $prod): ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light bg-opacity-50 border">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; min-width: 36px;">
                            #<?= $rank++ ?>
                        </div>
                        <div class="rounded-3 border overflow-hidden d-flex align-items-center justify-content-center bg-white shadow-sm" style="width: 36px; height: 36px; min-width: 36px;">
                            <img src="assets/images/<?= htmlspecialchars($prod['image'] ?? 'default_product.png') ?>" alt="Product" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px; font-size: 0.95rem;" title="<?= htmlspecialchars($prod['name']) ?>">
                            <?= htmlspecialchars($prod['name']) ?>
                        </div>
                    </div>
                    <div class="text-warning fw-bold bg-white px-3 py-1 rounded-pill shadow-sm border border-warning-subtle" style="font-size: 0.85rem;">
                        <?= number_format($prod['count']) ?> ครั้ง
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($top_products_data)): ?>
                <div class="text-center text-muted py-3">ไม่มีข้อมูล</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Recent Transactions -->
<div class="row g-4 mb-4">
    <!-- 1. รายการเบิกล่าสุด -->
    <div class="col-12">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i>รายการเบิกล่าสุด</h6>
                <a href="pages/reports.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm" style="font-size: 0.75rem;">ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            
            <div class="table-responsive flex-grow-1">
                <table class="table align-middle table-transparent mb-0 text-nowrap" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th class="ps-3 text-muted fw-bold pb-2" style="font-size: 0.75rem;">สินค้า</th>
                            <th class="text-muted fw-bold pb-2" style="font-size: 0.75rem;">SERIAL</th>
                            <th class="text-muted fw-bold pb-2" style="font-size: 0.75rem;">ราคาต่อหน่วย</th>
                            <th class="text-muted fw-bold pb-2" style="font-size: 0.75rem;">เลขครุภัณฑ์</th>
                            <th class="text-muted fw-bold pb-2 text-center" style="font-size: 0.75rem;">รูปถ่าย</th>
                            <th class="text-muted fw-bold pb-2" style="font-size: 0.75rem;">ผู้เบิก</th>
                            <th class="text-muted fw-bold pb-2 pe-3" style="font-size: 0.75rem;">วันที่เบิก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_data as $row): ?>
                            <tr class="border-bottom">
                                <td class="ps-3 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 border overflow-hidden d-flex align-items-center justify-content-center bg-light" style="width: 42px; height: 42px; min-width: 42px;">
                                            <img src="assets/images/<?= htmlspecialchars($row['p_image'] ?? 'default_product.png') ?>" alt="Product" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                        </div>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 150px; font-size: 0.9rem;" title="<?= htmlspecialchars($row['p_name']) ?>"><?= htmlspecialchars($row['p_name']) ?></div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold" style="color: #ec4899; font-size: 0.85rem; font-family: monospace;">
                                        <?= htmlspecialchars($row['serial_code'] ?? 'SN'.str_pad($row['id'] ?? rand(1000,9999), 10, '0', STR_PAD_LEFT)) ?>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold text-primary" style="font-size: 0.9rem;">
                                        ฿<?= number_format($row['price'], 2) ?>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="text-muted" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars($row['asset_number'] ?? '7440-001-0001-60-'.str_pad($row['id'] ?? rand(1, 99), 4, '0', STR_PAD_LEFT)) ?>
                                    </div>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if (!empty($row['image'])): ?>
                                        <a href="assets/images/<?= htmlspecialchars($row['image']) ?>" target="_blank" class="d-inline-block rounded overflow-hidden border shadow-sm transition-all hover-shadow-sm" style="width: 45px; height: 35px;" title="คลิกเพื่อดูรูปเต็ม">
                                            <img src="assets/images/<?= htmlspecialchars($row['image']) ?>" alt="Condition" style="width: 100%; height: 100%; object-fit: cover;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <div class="text-dark fw-bold d-flex align-items-center gap-2" title="<?= htmlspecialchars($row['u_name']) ?>">
                                        <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-light border" style="width: 24px; height: 24px; min-width: 24px;">
                                            <?php if (!empty($row['u_image']) && $row['u_image'] !== 'default_user.png'): ?>
                                                <img src="assets/images/<?= htmlspecialchars($row['u_image']) ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-user text-secondary" style="font-size: 0.7rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?= htmlspecialchars($row['u_name']) ?>
                                    </div>
                                </td>
                                <td class="pe-3 py-3" style="white-space: nowrap;">
                                    <span class="badge bg-light text-muted fw-normal px-2 py-1" style="font-size: 0.75rem; border: 1px solid #f1f5f9;">
                                        <?= date('d/m/Y H:i', strtotime($row['borrowed_at'])) ?>
                                    </span>
                                </td>
                             </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_data)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">ไม่มีรายการเบิก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Line Chart: สถิติการเบิกรายเดือน
    const ctxBorrow = document.getElementById('borrowChart').getContext('2d');
    
    // Add background gradient
    const gradient = ctxBorrow.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
    
    new Chart(ctxBorrow, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'จำนวนการเบิก (ครั้ง)',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    cornerRadius: 8,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
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
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // 2. Doughnut Chart: หมวดหมู่สินค้าที่เบิกมากที่สุด
    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCategory, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($categories_data, 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($categories_data, 'count')) ?>,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#10b981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    cornerRadius: 8,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)'
                }
            }
        }
    });
});
</script>

<style>
    .hover-bg-light:hover { background-color: #f8fafc; transition: 0.2s; }
    .transition-all { transition: all 0.2s ease-in-out; }
</style>

<?php require_once 'includes/footer.php'; ?>
