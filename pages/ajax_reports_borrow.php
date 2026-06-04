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
        0 => "u_name",
        1 => "p_name",
        2 => "serial_code",
        3 => "asset_number",
        4 => "building", // we will just sort by building for location
        5 => "b.borrowed_at",
        6 => "p.price"
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
    $sql = "SELECT b.*, ps.serial_code, p.name as p_name, p.price, CONCAT(u.firstname, ' ', u.lastname) as u_name " . $baseSQL . " ORDER BY $orderBy $orderDir";
    if ($length != -1) {
        $sql .= " LIMIT $length OFFSET $start";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchParams);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dataArray = [];
    foreach ($data as $row) {
        $location = '';
        if (!empty($row['building'])) {
            $location .= '<span class="d-block"><i class="fas fa-building text-muted me-1"></i>' . htmlspecialchars($row['building']) . '</span>';
        }
        if (!empty($row['department'])) {
            $location .= '<span class="d-block text-muted"><i class="fas fa-users me-1"></i>' . htmlspecialchars($row['department']) . '</span>';
        }
        
        $asset_number = !empty($row['asset_number']) ? htmlspecialchars($row['asset_number']) : '-';

        $dataArray[] = [
            '<div class="fw-bold"><i class="fas fa-user-circle text-muted me-2"></i>' . htmlspecialchars($row['u_name']) . '</div>',
            '<div class="fw-bold text-dark">' . htmlspecialchars($row['p_name']) . '</div>',
            '<code class="text-primary">' . htmlspecialchars($row['serial_code']) . '</code>',
            '<span class="text-muted small">' . $asset_number . '</span>',
            '<div class="small">' . $location . '</div>',
            '<span class="badge bg-light text-dark fw-normal">' . date('d/m/Y H:i', strtotime($row['borrowed_at'])) . '</span>',
            '<div class="text-end fw-bold text-primary">฿' . number_format($row['price'], 2) . '</div>'
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
