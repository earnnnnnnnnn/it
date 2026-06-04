<?php
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT b.*, CONCAT(u.firstname, ' ', u.lastname) as borrower_name 
                       FROM borrowings b 
                       JOIN users u ON b.borrower_id = u.id 
                       WHERE b.serial_id = ? 
                       ORDER BY b.borrowed_at DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

if (empty($history)) {
    echo '<div class="text-center py-4 text-muted">ไม่พบประวัติการเบิกสินค้าชิ้นนี้</div>';
    exit;
}
?>

<div class="table-responsive">
    <table class="table align-middle text-nowrap">
        <thead>
            <tr>
                <th>ผู้เบิก</th>
                <th>วันที่เบิก</th>
                <th>วันที่คืน</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $row): ?>
            <tr>
                <td><?= $row['borrower_name'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['borrowed_at'])) ?></td>
                <td><?= $row['returned_at'] ? date('d/m/Y H:i', strtotime($row['returned_at'])) : '-' ?></td>
                <td>
                    <?php if ($row['returned_at']): ?>
                        <span class="badge bg-success-subtle text-success">คืนแล้ว</span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning">กำลังใช้งาน</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
