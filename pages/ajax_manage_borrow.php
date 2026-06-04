<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Block non-admin users from modifying borrow records
if (in_array($action, ['update', 'delete']) && !in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
    exit;
}

try {
    switch ($action) {
        case 'get':
            // Get single borrowing record for editing
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT b.*, ps.serial_code, p.name as product_name, p.brand, p.model,
                       CONCAT(u.firstname, ' ', u.lastname) as borrower_name,
                       u.id as user_id
                FROM borrowings b
                JOIN product_serials ps ON b.serial_id = ps.id
                JOIN products p ON ps.product_id = p.id
                JOIN users u ON b.borrower_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $borrow = $stmt->fetch();

            if (!$borrow) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรายการเบิกนี้']);
                exit;
            }

            // Get dropdown options
            $buildings = $pdo->query("SELECT name FROM buildings ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $floors = $pdo->query("SELECT name FROM floors ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $departments = $pdo->query("SELECT name FROM departments ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $reasons = $pdo->query("SELECT label FROM reasons WHERE type = 'borrow' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
            $users = $pdo->query("SELECT id, CONCAT(firstname, ' ', lastname) as full_name FROM users ORDER BY firstname ASC")->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $borrow,
                'options' => [
                    'buildings' => $buildings,
                    'floors' => $floors,
                    'departments' => $departments,
                    'reasons' => $reasons,
                    'users' => $users
                ]
            ]);
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
                exit;
            }

            // Verify borrowing exists
            $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรายการเบิกนี้']);
                exit;
            }

            $borrower_id = intval($_POST['borrower_id'] ?? 0);
            $asset_number = trim($_POST['asset_number'] ?? '');
            $building = trim($_POST['building'] ?? '');
            $floor = trim($_POST['floor'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $borrowed_at = trim($_POST['borrowed_at'] ?? '');
            $returned_at = trim($_POST['returned_at'] ?? '');

            if (!$borrower_id) {
                echo json_encode(['success' => false, 'message' => 'กรุณาเลือกผู้เบิก']);
                exit;
            }

            $pdo->beginTransaction();

            $update_sql = "UPDATE borrowings SET 
                borrower_id = ?, 
                asset_number = ?, 
                building = ?, 
                floor = ?, 
                department = ?, 
                reason = ?,
                borrowed_at = ?";
            $update_params = [$borrower_id, $asset_number, $building, $floor, $department, $reason, $borrowed_at];

            // Handle returned_at and serial status
            if (!empty($returned_at)) {
                $update_sql .= ", returned_at = ?";
                $update_params[] = $returned_at;

                // Update serial status to available when returned
                $stmt_serial = $pdo->prepare("SELECT serial_id FROM borrowings WHERE id = ?");
                $stmt_serial->execute([$id]);
                $serial_row = $stmt_serial->fetch();
                if ($serial_row) {
                    $pdo->prepare("UPDATE product_serials SET status = 'available' WHERE id = ?")->execute([$serial_row['serial_id']]);
                }
            } else {
                $update_sql .= ", returned_at = NULL";
                
                // If clearing returned_at, set serial back to borrowed
                $stmt_serial = $pdo->prepare("SELECT serial_id FROM borrowings WHERE id = ?");
                $stmt_serial->execute([$id]);
                $serial_row = $stmt_serial->fetch();
                if ($serial_row) {
                    $pdo->prepare("UPDATE product_serials SET status = 'borrowed' WHERE id = ?")->execute([$serial_row['serial_id']]);
                }
            }

            $update_sql .= " WHERE id = ?";
            $update_params[] = $id;

            $stmt_update = $pdo->prepare($update_sql);
            $stmt_update->execute($update_params);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'อัปเดตรายการเบิกเรียบร้อยแล้ว']);
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
                exit;
            }

            $pdo->beginTransaction();

            // Get serial_id to restore status
            $stmt = $pdo->prepare("SELECT serial_id, returned_at FROM borrowings WHERE id = ?");
            $stmt->execute([$id]);
            $borrow = $stmt->fetch();

            if (!$borrow) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'ไม่พบรายการเบิกนี้']);
                exit;
            }

            // If this borrowing was not returned, restore serial to available
            if (empty($borrow['returned_at'])) {
                $pdo->prepare("UPDATE product_serials SET status = 'available' WHERE id = ?")->execute([$borrow['serial_id']]);
            }

            // Delete the borrowing record
            $pdo->prepare("DELETE FROM borrowings WHERE id = ?")->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'ลบรายการเบิกเรียบร้อยแล้ว']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action ไม่ถูกต้อง']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
