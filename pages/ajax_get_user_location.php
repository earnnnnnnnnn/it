<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_GET['name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing name parameter']);
    exit;
}

$name = trim($_GET['name']);

try {
    // Clean the name (remove any (@username) part if it accidentally gets passed)
    $name = preg_replace('/ \(@.*?\)$/', '', $name);

    // Find user ID
    $stmt_user = $pdo->prepare("SELECT id FROM users WHERE TRIM(CONCAT(firstname, ' ', IFNULL(lastname, ''))) = ?");
    $stmt_user->execute([$name]);
    $user = $stmt_user->fetch();
    
    if ($user) {
        // Find their most recent borrowing location
        $stmt_loc = $pdo->prepare("SELECT building, floor, department FROM borrowings WHERE borrower_id = ? ORDER BY borrowed_at DESC LIMIT 1");
        $stmt_loc->execute([$user['id']]);
        $loc = $stmt_loc->fetch(PDO::FETCH_ASSOC);
        
        // Find their currently borrowed items (status = 'borrowed')
        $stmt_summary = $pdo->prepare("
            SELECT COUNT(ps.id) as total_items, SUM(p.price) as total_value
            FROM product_serials ps
            JOIN products p ON ps.product_id = p.id
            JOIN borrowings b ON ps.id = b.serial_id
            WHERE b.borrower_id = ? AND ps.status = 'borrowed'
            AND b.id = (SELECT MAX(id) FROM borrowings WHERE serial_id = ps.id)
        ");
        $stmt_summary->execute([$user['id']]);
        $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);

        if (!$loc) $loc = [];
        $loc['borrowed_count'] = $summary['total_items'] ?? 0;
        $loc['borrowed_value'] = $summary['total_value'] ?? 0;
        
        echo json_encode(['success' => true, 'data' => $loc]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
