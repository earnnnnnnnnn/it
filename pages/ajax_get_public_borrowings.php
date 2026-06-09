<?php
require_once '../config/db.php';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    
    $sql = "SELECT b.borrowed_at, ps.serial_code, p.name as p_name, p.price, p.image as p_image, 
                   b.asset_number, b.building, b.floor, b.department, b.image 
            FROM borrowings b 
            JOIN product_serials ps ON b.serial_id = ps.id 
            JOIN products p ON ps.product_id = p.id 
            ORDER BY b.borrowed_at DESC 
            LIMIT " . intval($limit);
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
