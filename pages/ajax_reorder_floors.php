<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order = $data['order'] ?? [];

if (!empty($order)) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE floors SET sort_order = ? WHERE id = ?");
        foreach ($order as $index => $id) {
            $stmt->execute([$index + 1, $id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No data']);
}
?>
