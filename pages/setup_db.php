<?php
require_once '../config/db.php';

try {
    // Add rental_price column
    $pdo->exec("ALTER TABLE products ADD COLUMN rental_price DECIMAL(10,2) DEFAULT '0.00' AFTER price");
    echo "Added rental_price column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "rental_price column already exists.<br>";
    } else {
        echo "Error adding rental_price: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add rental_duration column
    $pdo->exec("ALTER TABLE products ADD COLUMN rental_duration INT DEFAULT '0' AFTER rental_price");
    echo "Added rental_duration column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "rental_duration column already exists.<br>";
    } else {
        echo "Error adding rental_duration: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add remark column
    $pdo->exec("ALTER TABLE products ADD COLUMN remark TEXT NULL");
    echo "Added remark column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "remark column already exists.<br>";
    } else {
        echo "Error adding remark: " . $e->getMessage() . "<br>";
    }
}

echo "<br><b style='color: green;'>อัปเดตฐานข้อมูลสำเร็จเรียบร้อยครับ!</b><br>กรุณากลับไปที่หน้าเพิ่มสินค้าแล้วกดบันทึกใหม่อีกครั้งครับ (คุณสามารถลบไฟล์ setup_db.php นี้ทิ้งได้เลย)";
?>
