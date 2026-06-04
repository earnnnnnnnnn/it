<?php
require_once 'config/db.php';

function findConstraintName($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT constraint_name FROM information_schema.key_column_usage \
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL"
    );
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn();
}

function safeDropForeignKey($pdo, $table, $column) {
    $constraint = findConstraintName($pdo, $table, $column);
    if ($constraint) {
        $pdo->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        return $constraint;
    }
    return null;
}

try {
    $pdo->beginTransaction();

    echo "<div style='max-width: 720px; margin: 20px auto;'>";
    echo "<h4 class='mb-4'>🔧 แก้ไขข้อจำกัด Foreign Key</h4>";

    $updates = [
        ['table' => 'borrowings', 'column' => 'serial_id', 'ref_table' => 'product_serials', 'ref_column' => 'id'],
        ['table' => 'borrowings', 'column' => 'borrower_id', 'ref_table' => 'users', 'ref_column' => 'id'],
        ['table' => 'stock_imports', 'column' => 'admin_id', 'ref_table' => 'users', 'ref_column' => 'id'],
        ['table' => 'stock_import_items', 'column' => 'product_id', 'ref_table' => 'products', 'ref_column' => 'id'],
    ];

    foreach ($updates as $index => $info) {
        $table = $info['table'];
        $column = $info['column'];
        $refTable = $info['ref_table'];
        $refColumn = $info['ref_column'];

        $oldConstraint = safeDropForeignKey($pdo, $table, $column);
        if ($oldConstraint) {
            echo "<p>✓ ลบ foreign key เดิมสำหรับ <strong>{$table}.{$column}</strong> ({$oldConstraint})</p>";
        } else {
            echo "<p>ℹ️ ไม่พบ foreign key เดิมสำหรับ <strong>{$table}.{$column}</strong> หรือถูกลบไปแล้ว</p>";
        }

        $constraintName = "{$table}_{$column}_fk";
        $pdo->exec(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` \
             FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}`(`{$refColumn}`) ON DELETE CASCADE"
        );

        echo "<p>✓ เพิ่ม foreign key ใหม่: <strong>{$table}.{$column} → {$refTable}.{$refColumn}</strong> ON DELETE CASCADE</p>";
    }

    $pdo->commit();

    echo "<div class='alert alert-success mt-4'>
        <h5>✓ แก้ไขข้อจำกัด Foreign Key สำเร็จ</h5>
        <p>ตารางฐานข้อมูลทั้งหมดได้เชื่อมโยงกับ ON DELETE CASCADE</p>
        <p>ตอนนี้คุณสามารถลบผู้ใช้หรือสินค้าที่เกี่ยวข้องได้โดยไม่ติดข้อผิดพลาดฐานข้อมูลแล้ว</p>
    </div>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div class='alert alert-danger mt-4'>
        <h5>✗ เกิดข้อผิดพลาด</h5>
        <pre style='white-space: pre-wrap;'>" . htmlspecialchars($e->getMessage()) . "</pre>
    </div>";
}
?>

