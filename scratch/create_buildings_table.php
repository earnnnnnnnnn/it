<?php
require_once 'config/db.php';

try {
    // 1. Create table
    $sql = "CREATE TABLE IF NOT EXISTS buildings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql);
    echo "Table 'buildings' created successfully.\n";

    // 2. Default buildings list
    $default_buildings = [
        "อาคารอำนวยการ",
        "อาคาร 58 ปี",
        "อาคารคลังน้ำเกลือ",
        "อาคารคลังพัสดุ",
        "อาคารจิตตารมย์",
        "อาคารชวนปรีชาเวทย์",
        "อาคารธัญรักษ์นนท์ (114 เตียง)",
        "อาคารชัชรักษ์",
        "อาคารรักษ์พล",
        "อาคารธรรมรักษ์",
        "อาคารนิติเวช",
        "อาคารปิติพร",
        "อาคารศูนย์ผ่าตัดวันเดียวกลับ",
        "อาคารศูนย์หลักฐานเชิงประจักษ์",
        "อาคารศูนย์แพทยศาสตร์ศึกษาชั้นคลินิก",
        "อาคารสำราญสำรวจวิว (75 ปี)",
        "อาคารลู่วิทา",
        "อาคารสูตินิเวช (เก่า)",
        "อาคารอภัยภูเบศรเดิมป่า",
        "อาคารอาชีวเวชกรรม",
        "อาคารอุบัติเหตุและฉุกเฉิน",
        "อาคารเครื่องมือแพทย์",
        "อาคารเฉลิมพระเกียรติฯ",
        "อาคารเพชรรัตน์",
        "อาคารโรงครัว",
        "อาคารไฟฟ้า"
    ];

    // 3. Populate default buildings
    $stmt = $pdo->prepare("INSERT IGNORE INTO buildings (name, sort_order) VALUES (?, ?)");
    foreach ($default_buildings as $index => $bName) {
        $stmt->execute([$bName, $index + 1]);
    }
    echo "Populated default buildings successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
