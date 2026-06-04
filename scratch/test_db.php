<?php
require_once 'c:/xampp/htdocs/it/config/db.php';

try {
    $search = "พร้อม";
    $sql = "FROM product_serials ps 
            JOIN products p ON ps.product_id = p.id 
            LEFT JOIN (
                SELECT b1.* FROM borrowings b1
                JOIN (SELECT serial_id, MAX(id) as max_id FROM borrowings GROUP BY serial_id) b2 ON b1.id = b2.max_id
            ) b ON ps.id = b.serial_id
            LEFT JOIN users u ON b.borrower_id = u.id";

    $whereSql = " WHERE (p.name LIKE :search 
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
    
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare("SELECT ps.*, p.name, p.brand, p.model, p.category, p.image, p.price,
                           b.asset_number as b_asset, b.borrowed_at, b.building, b.floor, b.department,
                           CONCAT(u.firstname, ' ', u.lastname) as borrower_name " . $sql . $whereSql . " LIMIT 5");
    $stmt->execute([':search' => "%$search%"]);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query executed successfully. Found " . count($results) . " results.\n";
    print_r($results);
} catch (Exception $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}
?>





