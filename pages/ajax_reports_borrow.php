<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

try {
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    // Column mapping for sorting
    $columns = [
        0 => "p.name",
        1 => "asset_number",
        2 => "b.borrowed_at", // Status is constant so we use date as fallback
        3 => "u.firstname",
        4 => "p.price",
        5 => "b.borrowed_at"
    ];

    $orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 5;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    $orderBy = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : "b.borrowed_at";

    if (!in_array(strtolower($orderDir), ['asc', 'desc'])) {
        $orderDir = 'desc';
    }

    $searchQuery = "";
    $searchParams = [];

    if (!empty($searchValue)) {
        $searchQuery = " AND (u.firstname LIKE ? 
                            OR u.lastname LIKE ? 
                            OR p.name LIKE ? 
                            OR ps.serial_code LIKE ? 
                            OR asset_number LIKE ? 
                            OR building LIKE ? 
                            OR department LIKE ?) ";
        $searchParams = array_fill(0, 7, "%$searchValue%");
    }

    // Base query
    $baseSQL = "FROM borrowings b 
                JOIN product_serials ps ON b.serial_id = ps.id 
                JOIN products p ON ps.product_id = p.id 
                JOIN users u ON b.borrower_id = u.id 
                WHERE 1=1 " . $searchQuery;

    // Get total records without filtering
    $stmtTotal = $pdo->query("SELECT COUNT(b.id) FROM borrowings b");
    $recordsTotal = $stmtTotal->fetchColumn();

    // Get total records with filtering
    if (!empty($searchValue)) {
        $stmtFiltered = $pdo->prepare("SELECT COUNT(b.id) " . $baseSQL);
        $stmtFiltered->execute($searchParams);
        $recordsFiltered = $stmtFiltered->fetchColumn();
    } else {
        $recordsFiltered = $recordsTotal;
    }

    // Fetch data
    $sql = "SELECT b.*, ps.serial_code, p.name as p_name, p.brand, p.model, p.image as p_image, p.price, CONCAT(u.firstname, ' ', u.lastname) as u_name " . $baseSQL . " ORDER BY $orderBy $orderDir";
    if ($length != -1) {
        $sql .= " LIMIT $length OFFSET $start";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchParams);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dataArray = [];
    foreach ($data as $row) {
        // 1. Product Display
        $img_src = !empty($row['p_image']) ? '../assets/images/' . $row['p_image'] : '../assets/images/default_product.png';
        $brand_model = (!empty($row['brand']) ? htmlspecialchars($row['brand']) : '') . ' ' . (!empty($row['model']) ? htmlspecialchars($row['model']) : '');
        $product_display = '<div class="d-flex align-items-center">
                                <img src="' . htmlspecialchars($img_src) . '" class="rounded me-2 shadow-sm border" style="width: 32px; height: 32px; object-fit: cover; background: #fff;" alt="Product Image">
                                <div style="line-height: 1.2;">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">' . htmlspecialchars($row['p_name']) . '</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">' . trim($brand_model) . '</div>
                                </div>
                            </div>';

        // 2. Asset & Serial Display
        $asset_number = !empty($row['asset_number']) ? htmlspecialchars($row['asset_number']) : '-';
        $serial_code = !empty($row['serial_code']) ? htmlspecialchars($row['serial_code']) : '-';
        $reason = !empty($row['reason']) ? '<div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-comment-dots me-1 text-info"></i>' . htmlspecialchars($row['reason']) . '</div>' : '';
        $asset_serial_display = '
            <div class="fw-bold text-dark" style="font-size: 0.75rem; white-space: nowrap;">' . $asset_number . '</div>
            <div style="font-size: 0.7rem; white-space: nowrap;"><span class="text-muted">S/N:</span> <span class="text-danger">' . $serial_code . '</span></div>
            ' . $reason;

        // 3. Image Display
        if (!empty($row['image'])) {
            $status_badge = '<div class="text-center"><a href="../assets/images/' . htmlspecialchars($row['image']) . '" target="_blank" class="d-inline-block rounded overflow-hidden border shadow-sm transition-all hover-shadow-sm" style="width: 45px; height: 35px;" title="คลิกเพื่อดูรูปเต็ม"><img src="../assets/images/' . htmlspecialchars($row['image']) . '" style="width: 100%; height: 100%; object-fit: cover;"></a></div>';
        } else {
            $status_badge = '<div class="text-center text-muted small">-</div>';
        }

        // 4. Holder & Location Display
        $building = !empty($row['building']) ? htmlspecialchars($row['building']) : '';
        $department = !empty($row['department']) ? htmlspecialchars($row['department']) : '';
        
        $loc_html = '';
        if ($building) {
            $loc_html .= '<div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-map-marker-alt text-secondary me-2" style="width: 14px; text-align: center;"></i>' . $building . '</div>';
        }
        if ($department) {
            $loc_html .= '<div class="text-muted" style="font-size: 0.8rem;"><i class="fas fa-users text-info me-2" style="width: 14px; text-align: center;"></i>' . $department . '</div>';
        }
        if (!$building && !$department) {
            $loc_html = '<div class="text-muted" style="font-size: 0.85rem;">-</div>';
        }

        $location_display = '
            <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;"><i class="fas fa-user-circle text-secondary me-2"></i>' . htmlspecialchars($row['u_name']) . '</div>
            ' . $loc_html;

        // 5. Price Display
        $price_display = '<span class="text-primary fw-bold">฿' . number_format($row['price'], 2) . '</span>';

        // 6. Date Display
        $date_display = '<span class="text-muted" style="font-size: 0.85rem;">' . date('d/m/Y H:i', strtotime($row['borrowed_at'])) . '</span>';

        $dataArray[] = [
            $product_display,
            $asset_serial_display,
            $status_badge,
            $location_display,
            $price_display,
            $date_display
        ];
    }

    $response = [
        "draw" => $draw,
        "recordsTotal" => intval($recordsTotal),
        "recordsFiltered" => intval($recordsFiltered),
        "data" => $dataArray
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
}
