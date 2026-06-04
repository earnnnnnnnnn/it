<?php
require_once 'config/db.php';

try {
    // 1. Create floors table
    $sqlFloors = "CREATE TABLE IF NOT EXISTS floors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlFloors);
    echo "Table 'floors' created successfully.\n";

    // 2. Create departments table
    $sqlDepts = "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlDepts);
    echo "Table 'departments' created successfully.\n";

    // 3. Default floors list
    $default_floors = [
        "ชั้น 1",
        "ชั้น 2",
        "ชั้น 3",
        "ชั้น 4",
        "ชั้น 5",
        "ชั้น 6",
        "ชั้น 7"
    ];

    // 4. Default departments list
    $default_depts = [
        "001-ธุรการ",
        "002-สนาม",
        "003-โรงรถ",
        "004-ยาม",
        "005-นิติเวช",
        "006-ประชาสัมพันธ์",
        "007-พ.ร.ส.",
        "008-เวชบันทึก",
        "009-เวชระเบียน",
        "010-ศูนย์พัฒนาคุณภาพ",
        "014-สำนักงานการเงิน",
        "015-ศูนย์จัดเก็บรายได้ (งานประกันสุขภาพ)",
        "016-กลุ่มงานสื่อผสมการแพทย์",
        "017-ช่วยกลางเวชบันทึกบริการอาชีว",
        "018-การพยาบาล",
        "019-อ๊าฟฟอก- ตัดเย็บ",
        "020-ศูนย์เฉพาะกิจ",
        "021-ศูนย์เปล",
        "022-โภชนาการ",
        "023-กลุ่มงานพัสดุ",
        "024-โรงซ่อมครุภัณฑ์",
        "025-ไฟฟ้า(งานซ่อมบำรุง)",
        "026-ประปา",
        "027-บำบัดน้ำเสีย",
        "028-จัดซื้อและบำรุงรักษาเครื่องมือแพทย์",
        "029-คลังยาและเวชภัณฑ์",
        "030-น้ำเกลือ(อาคารเกลือ)",
        "031-พยาธิคลินิก",
        "032-ธนาคารเลือด",
        "033-เครื่องมือพิเศษ",
        "034-ไตเทียม",
        "035-พยาธิกายวิภาค",
        "036-จ่ายยาผู้ป่วยใน",
        "037-จ่ายยาผู้ป่วยนอก",
        "038-จ่ายยาผู้ป่วยในสาขา",
        "039-รังสีวิทยา",
        "040-กายอุปกรณ์"
    ];

    // Populate floors
    $stmtFloor = $pdo->prepare("INSERT IGNORE INTO floors (name, sort_order) VALUES (?, ?)");
    foreach ($default_floors as $index => $fName) {
        $stmtFloor->execute([$fName, $index + 1]);
    }
    echo "Populated default floors successfully.\n";

    // Populate departments
    $stmtDept = $pdo->prepare("INSERT IGNORE INTO departments (name, sort_order) VALUES (?, ?)");
    foreach ($default_depts as $index => $dName) {
        $stmtDept->execute([$dName, $index + 1]);
    }
    echo "Populated default departments successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
