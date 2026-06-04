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
        1 => "p.category",
        2 => "p.price",
        3 => "total",
        4 => "available",
        5 => "borrowed",
        6 => "broken",
        7 => "total_value",
        8 => "available" // sort stock level by available count
    ];

    $orderColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    $orderBy = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : "p.name";

    if (!in_array(strtolower($orderDir), ['asc', 'desc'])) {
        $orderDir = 'asc';
    }

    $searchQuery = "";
    $searchParams = [];

    if (!empty($searchValue)) {
        $searchQuery = " AND (p.name LIKE ? 
                            OR p.sku LIKE ? 
                            OR p.brand LIKE ? 
                            OR p.category LIKE ?) ";
        $searchParams = array_fill(0, 4, "%$searchValue%");
    }

    // Base SQL for counting and filtering
    $whereClause = "WHERE 1=1 " . $searchQuery;

    // Get total records without filtering
    $stmtTotal = $pdo->query("SELECT COUNT(id) FROM products p");
    $recordsTotal = $stmtTotal->fetchColumn();

    // Get total records with filtering
    if (!empty($searchValue)) {
        $stmtFiltered = $pdo->prepare("SELECT COUNT(id) FROM products p " . $whereClause);
        $stmtFiltered->execute($searchParams);
        $recordsFiltered = $stmtFiltered->fetchColumn();
    } else {
        $recordsFiltered = $recordsTotal;
    }

    // Fetch data
    $sql = "SELECT p.name, p.sku, p.brand, p.category, p.image, p.price, COUNT(ps.id) as total, 
                SUM(CASE WHEN ps.status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN ps.status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
                SUM(CASE WHEN ps.status IN ('broken', 'lost', 'repairing') THEN 1 ELSE 0 END) as broken,
                (p.price * COUNT(ps.id)) as total_value
            FROM products p
            LEFT JOIN product_serials ps ON p.id = ps.product_id
            $whereClause
            GROUP BY p.id
            ORDER BY $orderBy $orderDir";

    if ($length != -1) {
        $sql .= " LIMIT $length OFFSET $start";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchParams);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dataArray = [];
    foreach ($data as $row) {
        // Determine image src
        $imgSrc = !empty($row['image']) ? '../assets/images/' . htmlspecialchars($row['image']) : '../assets/images/default_product.png';
        $imgHtml = '<img src="' . $imgSrc . '" class="rounded border" width="40" height="40" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">';
        
        $productHtml = '<div class="d-flex align-items-center gap-3">
                            ' . $imgHtml . '
                            <div>
                                <div class="fw-bold">' . htmlspecialchars($row['name']) . '</div>
                                <div class="text-muted small">' . htmlspecialchars($row['sku']) . ' | ' . htmlspecialchars($row['brand']) . '</div>
                            </div>
                        </div>';

        // Stock level badge
        if ($row['available'] == 0) {
            $stockLevel = '<span class="badge bg-danger">หมดสต็อก</span>';
        } elseif ($row['available'] <= 5) {
            $stockLevel = '<span class="badge bg-warning">ใกล้หมด</span>';
        } else {
            $stockLevel = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">ปกติ</span>';
        }

        $dataArray[] = [
            $productHtml,
            '<span class="badge bg-light text-dark fw-normal border">' . htmlspecialchars($row['category']) . '</span>',
            '<div class="text-end">฿' . number_format($row['price'], 2) . '</div>',
            '<div class="text-center">' . number_format($row['total']) . '</div>',
            '<div class="text-center text-success fw-bold">' . number_format($row['available']) . '</div>',
            '<div class="text-center text-primary fw-bold">' . number_format($row['borrowed']) . '</div>',
            '<div class="text-center text-danger fw-bold">' . number_format($row['broken']) . '</div>',
            '<div class="text-end fw-bold">฿' . number_format($row['total_value'], 2) . '</div>',
            $stockLevel
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
