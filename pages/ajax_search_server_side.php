<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

// DataTables Server-side processing logic
$requestData = $_REQUEST;

$columns = [
    0 => 'p.name',
    1 => 'ps.serial_code',
    2 => 'ps.status',
    3 => 'u.firstname',
    4 => 'p.price',
    5 => 'b.borrowed_at'
];

// Base query
$sql = "FROM product_serials ps 
        JOIN products p ON ps.product_id = p.id 
        LEFT JOIN (
            SELECT b1.* FROM borrowings b1
            JOIN (SELECT serial_id, MAX(id) as max_id FROM borrowings GROUP BY serial_id) b2 ON b1.id = b2.max_id
        ) b ON ps.id = b.serial_id
        LEFT JOIN users u ON b.borrower_id = u.id";

$has_where = false;
if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    $sql .= " WHERE b.borrower_id = " . (int)$_SESSION['user_id'];
    $has_where = true;
}

// Get total records
$totalDataStmt = $pdo->query("SELECT COUNT(ps.id) " . $sql);
$totalData = $totalDataStmt->fetchColumn();
$totalFiltered = $totalData;

// Filtering
if (!empty($requestData['search']['value'])) {
    $search = $requestData['search']['value'];
    $whereSql = ($has_where ? " AND " : " WHERE ") . "(p.name LIKE :search 
                  OR p.sku LIKE :search
                  OR p.brand LIKE :search 
                  OR p.model LIKE :search 
                  OR p.category LIKE :search
                  OR p.spec LIKE :search
                  OR ps.serial_code LIKE :search 
                  OR b.asset_number LIKE :search 
                  OR b.building LIKE :search
                  OR b.floor LIKE :search
                  OR b.department LIKE :search
                  OR b.approver_name LIKE :search
                  OR b.reason LIKE :search
                  OR u.firstname LIKE :search
                  OR u.lastname LIKE :search
                  OR u.username LIKE :search
                  OR u.email LIKE :search
                  OR CONCAT(u.firstname, ' ', u.lastname) LIKE :search
                  OR ps.status LIKE :search
                  OR p.price LIKE :search
                  OR DATE_FORMAT(b.borrowed_at, '%d/%m/%Y') LIKE :search
                  OR (
                      CASE ps.status
                          WHEN 'available' THEN 'พร้อมใช้งาน'
                          WHEN 'borrowed' THEN 'ถูกเบิกใช้งาน'
                          WHEN 'repairing' THEN 'ส่งซ่อม'
                          WHEN 'broken' THEN 'ชำรุด'
                          WHEN 'lost' THEN 'สูญหาย'
                          ELSE ps.status
                      END
                  ) LIKE :search)";
    
    $filteredStmt = $pdo->prepare("SELECT COUNT(ps.id) " . $sql . $whereSql);
    $filteredStmt->execute([':search' => "%$search%"]);
    $totalFiltered = $filteredStmt->fetchColumn();
    
    $sql .= $whereSql;
}

// Sorting
$orderColumnIndex = isset($requestData['order'][0]['column']) ? intval($requestData['order'][0]['column']) : 0;
$orderDir = isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc' ? 'DESC' : 'ASC';
$orderBy = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : $columns[0];

$sql .= " ORDER BY " . $orderBy . " " . $orderDir;

// Pagination
$start = isset($requestData['start']) ? intval($requestData['start']) : 0;
$length = isset($requestData['length']) ? intval($requestData['length']) : 10;
$sql .= " LIMIT " . $start . " ," . $length;

// Final Query
$stmt = $pdo->prepare("SELECT ps.*, p.name, p.brand, p.model, p.category, p.image, p.price,
                       b.id as borrow_id, b.asset_number as b_asset, b.borrowed_at, b.returned_at, b.building, b.floor, b.department, b.reason, b.notes,
                       CONCAT(u.firstname, ' ', u.lastname) as borrower_name " . $sql);

if (!empty($requestData['search']['value'])) {
    $stmt->execute([':search' => "%$search%"]);
} else {
    $stmt->execute();
}

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $nestedData = [];
    
    // Product Info
    $image = $row['image'] ? $row['image'] : 'default_product.png';
    $productHtml = '
        <div class="d-flex align-items-center gap-2">
            <img src="../assets/images/'.$image.'" class="rounded border" width="32" height="32" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">
            <div>
                <div class="fw-bold text-dark mb-0" style="line-height: 1.2;">'.$row['name'].'</div>
                <div class="text-muted" style="font-size: 0.75rem;">'.$row['brand'].' '.$row['model'].'</div>
            </div>
        </div>';
    
    // Asset/Serial Info
    $display_asset = ($row['status'] == 'borrowed' && !empty($row['b_asset'])) ? htmlspecialchars($row['b_asset']) : '-';
    $reason_text = ($row['status'] == 'borrowed' && !empty($row['reason'])) ? htmlspecialchars($row['reason']) : '';
    $notes_text = ($row['status'] == 'borrowed' && !empty($row['notes'])) ? nl2br(htmlspecialchars($row['notes'])) : '';
    
    $reasonHtml = $reason_text ? '<div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-comment-dots text-info me-1"></i>'.$reason_text.'</div>' : '';
    $notesHtml = $notes_text ? '<div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-sticky-note text-warning me-1"></i>'.$notes_text.'</div>' : '';
    
    $assetHtml = '
        <div class="mb-0" style="font-size: 0.8rem;">Asset: <span class="fw-bold">'.$display_asset.'</span></div>
        <div class="text-muted" style="font-size: 0.75rem;">S/N: <code>'.htmlspecialchars($row['serial_code']).'</code></div>' . $reasonHtml . $notesHtml;
    
    // Status Badge
    $statusClass = $row['status'] == 'available' ? 'success' : ($row['status'] == 'borrowed' ? 'warning' : 'secondary');
    $statusText = $row['status'] == 'available' ? 'พร้อมใช้งาน' : ($row['status'] == 'borrowed' ? 'ถูกเบิกใช้งาน' : $row['status']);
    $statusHtml = '<span class="badge rounded-pill bg-'.$statusClass.' bg-opacity-10 text-'.$statusClass.' px-2 py-1" style="font-size: 0.75rem;">'.$statusText.'</span>';
    
    // Borrower Info
    $borrowerHtml = '-';
    if ($row['status'] == 'borrowed' && $row['borrower_name']) {
        $dept_html = !empty($row['department']) ? '<div class="text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-users me-1 text-info"></i> '.htmlspecialchars($row['department']).'</div>' : '';
        $borrowerHtml = '
            <div class="fw-bold text-dark mb-0" style="font-size: 0.8rem; line-height: 1.2;"><i class="fas fa-user-circle me-1 text-muted"></i> '.$row['borrower_name'].'</div>
            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-location-dot me-1"></i> '.htmlspecialchars($row['building']).' '.htmlspecialchars($row['floor']).'</div>' . $dept_html;
    }
    
    $nestedData[] = $productHtml;
    $nestedData[] = $assetHtml;
    $nestedData[] = $statusHtml;
    $nestedData[] = $borrowerHtml;
    $nestedData[] = '<div class="text-end fw-bold text-primary">฿'.number_format($row['price'], 2).'</div>';
    
    // Date
    if ($row['status'] == 'borrowed' && $row['borrowed_at']) {
        $nestedData[] = '<div class="text-muted small">'.date('d/m/Y', strtotime($row['borrowed_at'])).'<br><span class="text-secondary" style="font-size: 0.75rem;"><i class="far fa-clock me-1"></i>'.date('H:i', strtotime($row['borrowed_at'])).'</span></div>';
    } else {
        $nestedData[] = '<span class="text-muted small">-</span>';
    }
    
    
    $data[] = $nestedData;
}

$json_data = [
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
];

echo json_encode($json_data);
