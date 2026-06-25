<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    exit('Unauthorized');
}

$filename = "inventory_report_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Adding BOM for UTF-8 correctly in Excel
echo "\xEF\xBB\xBF"; 

?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid black; padding: 5px; font-family: Tahoma, Arial, sans-serif; font-size: 14px; }
    th { background-color: #f2f2f2; font-weight: bold; }
</style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>สินค้า</th>
                <th>หมวดหมู่</th>
                <th>ราคา (บาท)</th>
                <th>ราคาเช่า (บาท)</th>
                <th>เลขครุภัณฑ์ (Asset No)</th>
                <th>S/N (Serial Number)</th>
                <th>สถานะ</th>
                <th>ผู้ถือครอง</th>
                <th>สถานที่ (อาคาร/ชั้น/แผนก)</th>
                <th>วันที่เบิก</th>
            </tr>
        </thead>
        <tbody>
<?php
$sql = "SELECT ps.*, p.name, p.category, p.price, p.rental_price,
        b.asset_number as b_asset, b.borrowed_at, b.building, b.floor, b.department,
        CONCAT(u.firstname, ' ', u.lastname) as borrower_name
        FROM product_serials ps 
        JOIN products p ON ps.product_id = p.id 
        LEFT JOIN (
            SELECT b1.* FROM borrowings b1
            JOIN (SELECT serial_id, MAX(id) as max_id FROM borrowings GROUP BY serial_id) b2 ON b1.id = b2.max_id
        ) b ON ps.id = b.serial_id
        LEFT JOIN users u ON b.borrower_id = u.id
        ORDER BY p.name ASC, ps.serial_code ASC";

$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusText = $row['status'] == 'available' ? 'พร้อมใช้งาน' : ($row['status'] == 'borrowed' ? 'ถูกเบิกใช้งาน' : $row['status']);
    
    $location = '-';
    $borrower = '-';
    $borrow_date = '-';
    $asset = '-';
    
    if ($row['status'] == 'borrowed') {
        $loc_parts = [];
        if (!empty($row['building'])) $loc_parts[] = $row['building'];
        if (!empty($row['floor'])) $loc_parts[] = "ชั้น " . $row['floor'];
        if (!empty($row['department'])) $loc_parts[] = "แผนก " . $row['department'];
        
        if (!empty($loc_parts)) {
            $location = implode(' / ', $loc_parts);
        }
        
        if (!empty($row['borrower_name'])) {
            $borrower = $row['borrower_name'];
        }
        
        if (!empty($row['borrowed_at'])) {
            $borrow_date = date('d/m/Y H:i', strtotime($row['borrowed_at']));
        }
        
        if (!empty($row['b_asset'])) {
            $asset = $row['b_asset'];
        }
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
    echo "<td>" . number_format($row['price'], 2) . "</td>";
    echo "<td>" . number_format((float)$row['rental_price'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($asset) . "</td>";
    // style='mso-number-format:"\@"' prevents Excel from formatting S/N as scientific notation
    echo "<td style='mso-number-format:\"\\@\"'>" . htmlspecialchars($row['serial_code']) . "</td>";
    echo "<td>" . htmlspecialchars($statusText) . "</td>";
    echo "<td>" . htmlspecialchars($borrower) . "</td>";
    echo "<td>" . htmlspecialchars($location) . "</td>";
    echo "<td>" . htmlspecialchars($borrow_date) . "</td>";
    echo "</tr>";
}
?>
        </tbody>
    </table>
</body>
</html>
