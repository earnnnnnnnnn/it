<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (($_SESSION['user_role'] ?? 'USER') !== 'SUPERADMIN') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ']);
    exit;
}

// Handle both JSON and FormData
if (isset($_POST['action'])) {
    $data = $_POST;
} else {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
}

if (!$data || empty($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

$action = $data['action'];

try {
    // ===== ADD PRODUCT =====
    if ($action === 'add_product') {
        if (empty($data['name']) || empty($data['sku'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อสินค้าและ SKU']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $image_name = 'default_product.png';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = 'prod_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $image_name)) {
                    $image_name = 'products/' . $image_name;
                } else {
                    $image_name = 'default_product.png';
                }
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, sku, brand, model, category, spec, price, rental_price, rental_duration, unit, min_alert, image, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'], $data['sku'], $data['brand'], $data['model'], $data['category'],
                $data['spec'] ?? '', $data['price'] ?? 0, 
                $data['rental_price'] ?? 0, !empty($data['rental_duration']) ? $data['rental_duration'] : null,
                $data['unit'] ?? 'ชิ้น', $data['min_alert'], $image_name,
                $data['remark'] ?? null
            ]);
            $product_id = $pdo->lastInsertId();

            // Handle Serials if provided
            if (isset($data['serials']) && !empty($data['serials'])) {
                $serials = is_array($data['serials']) ? $data['serials'] : explode(',', $data['serials']);
                $serials = array_unique(array_filter($serials));

                if (!empty($serials)) {
                    // Create Import Record
                    $reason = !empty($data['import_reason']) ? $data['import_reason'] : 'นำเข้าพร้อมเปิดตัวสินค้าใหม่';
                    $stmt_import = $pdo->prepare("INSERT INTO stock_imports (admin_id, reason) VALUES (?, ?)");
                    $stmt_import->execute([$_SESSION['user_id'], $reason]);
                    $import_id = $pdo->lastInsertId();

                    $stmt_item = $pdo->prepare("INSERT INTO stock_import_items (import_id, product_id, qty) VALUES (?, ?, ?)");
                    $stmt_item->execute([$import_id, $product_id, count($serials)]);
                    $item_id = $pdo->lastInsertId();

                    $stmt_serial = $pdo->prepare("INSERT INTO product_serials (product_id, serial_code) VALUES (?, ?)");
                    $stmt_sis = $pdo->prepare("INSERT INTO stock_import_serials (import_item_id, serial_code) VALUES (?, ?)");

                    foreach ($serials as $sc) {
                        $stmt_serial->execute([$product_id, $sc]);
                        $stmt_sis->execute([$item_id, $sc]);
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ===== EDIT PRODUCT =====
    elseif ($action === 'edit_product') {
        $sql = "UPDATE products SET name = ?, sku = ?, brand = ?, model = ?, category = ?, spec = ?, price = ?, rental_price = ?, rental_duration = ?, unit = ?, min_alert = ?, remark = ?";
        $params = [
            $data['name'], $data['sku'], $data['brand'], $data['model'], $data['category'],
            $data['spec'] ?? '', $data['price'] ?? 0, 
            $data['rental_price'] ?? 0, !empty($data['rental_duration']) ? $data['rental_duration'] : null,
            $data['unit'] ?? 'ชิ้น', $data['min_alert'], $data['remark'] ?? null
        ];

        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = 'prod_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $image_name)) {
                $sql .= ", image = ?";
                $params[] = 'products/' . $image_name;
            }
        }

        $sql .= " WHERE id = ?";
        $params[] = $data['id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE PRODUCT =====
    elseif ($action === 'delete_product') {
        $pdo->beginTransaction();
        // 1. Delete borrowings related to the serials of this product
        $pdo->prepare("DELETE FROM borrowings WHERE serial_id IN (SELECT id FROM product_serials WHERE product_id = ?)")->execute([$data['id']]);
        
        // 2. Delete stock import serials and items
        $pdo->prepare("DELETE FROM stock_import_serials WHERE import_item_id IN (SELECT id FROM stock_import_items WHERE product_id = ?)")->execute([$data['id']]);
        $pdo->prepare("DELETE FROM stock_import_items WHERE product_id = ?")->execute([$data['id']]);
        
        // 3. Delete related serials
        $pdo->prepare("DELETE FROM product_serials WHERE product_id = ?")->execute([$data['id']]);
        
        // 4. Delete product
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$data['id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    }

    // ===== ADD REASON =====
    elseif ($action === 'add_reason') {
        if (empty($data['label']) || empty($data['type'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
            exit;
        }
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM reasons WHERE type = ? AND label = ?");
        $check->execute([$data['type'], $data['label']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'เหตุผลนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO reasons (type, label) VALUES (?, ?)");
        $stmt->execute([$data['type'], $data['label']]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT REASON =====
    elseif ($action === 'edit_reason') {
        $stmt = $pdo->prepare("UPDATE reasons SET label = ? WHERE id = ?");
        $stmt->execute([$data['label'], $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE REASON =====
    elseif ($action === 'delete_reason') {
        $stmt = $pdo->prepare("DELETE FROM reasons WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD BUILDING =====
    elseif ($action === 'add_building') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่ออาคาร']);
            exit;
        }
        $name = trim($data['name']);
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM buildings WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'อาคารนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        // Get max sort_order
        $max = $pdo->query("SELECT MAX(sort_order) FROM buildings")->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("INSERT INTO buildings (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT BUILDING =====
    elseif ($action === 'edit_building') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        
        // Check duplicate excluding self
        $check = $pdo->prepare("SELECT id FROM buildings WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่ออาคารนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE buildings SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE BUILDING =====
    elseif ($action === 'delete_building') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM buildings WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD FLOOR =====
    elseif ($action === 'add_floor') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อชั้น']);
            exit;
        }
        $name = trim($data['name']);
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM floors WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชั้นนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        // Get max sort_order
        $max = $pdo->query("SELECT MAX(sort_order) FROM floors")->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("INSERT INTO floors (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT FLOOR =====
    elseif ($action === 'edit_floor') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        
        // Check duplicate excluding self
        $check = $pdo->prepare("SELECT id FROM floors WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่อชั้นนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE floors SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE FLOOR =====
    elseif ($action === 'delete_floor') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM floors WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD DEPARTMENT =====
    elseif ($action === 'add_department') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อแผนก']);
            exit;
        }
        $name = trim($data['name']);
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'แผนกนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        // Get max sort_order
        $max = $pdo->query("SELECT MAX(sort_order) FROM departments")->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("INSERT INTO departments (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT DEPARTMENT =====
    elseif ($action === 'edit_department') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        
        // Check duplicate excluding self
        $check = $pdo->prepare("SELECT id FROM departments WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่อแผนกนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE DEPARTMENT =====
    elseif ($action === 'delete_department') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD CATEGORY =====
    elseif ($action === 'add_category') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อหมวดหมู่']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'หมวดหมู่นี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $max = $pdo->query("SELECT MAX(sort_order) FROM categories")->fetchColumn() ?: 0;
        $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT CATEGORY =====
    elseif ($action === 'edit_category') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่อหมวดหมู่นี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE CATEGORY =====
    elseif ($action === 'delete_category') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD PRODUCT TYPE =====
    elseif ($action === 'add_product_type') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อประเภทสินค้า']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM product_types WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ประเภทสินค้านี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $max = $pdo->query("SELECT MAX(sort_order) FROM product_types")->fetchColumn() ?: 0;
        $stmt = $pdo->prepare("INSERT INTO product_types (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT PRODUCT TYPE =====
    elseif ($action === 'edit_product_type') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM product_types WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่อประเภทสินค้านี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE product_types SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE PRODUCT TYPE =====
    elseif ($action === 'delete_product_type') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM product_types WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== ADD UNIT =====
    elseif ($action === 'add_unit') {
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อหน่วยนับ']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM units WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'หน่วยนับนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $max = $pdo->query("SELECT MAX(sort_order) FROM units")->fetchColumn() ?: 0;
        $stmt = $pdo->prepare("INSERT INTO units (name, sort_order) VALUES (?, ?)");
        $stmt->execute([$name, $max + 1]);
        echo json_encode(['success' => true]);
    }

    // ===== EDIT UNIT =====
    elseif ($action === 'edit_unit') {
        if (empty($data['name']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $name = trim($data['name']);
        $check = $pdo->prepare("SELECT id FROM units WHERE name = ? AND id != ?");
        $check->execute([$name, $data['id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ชื่อหน่วยนับนี้มีอยู่ในระบบแล้ว']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE units SET name = ? WHERE id = ?");
        $stmt->execute([$name, $data['id']]);
        echo json_encode(['success' => true]);
    }

    // ===== DELETE UNIT =====
    elseif ($action === 'delete_unit') {
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM units WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
    }


    // ===== GET SERIALS =====
    elseif ($action === 'get_serials') {
        $stmt = $pdo->prepare("SELECT ps.*, b.borrowed_at, b.asset_number, b.building, b.floor, b.department, CONCAT(u.firstname, ' ', u.lastname) as borrower 
                               FROM product_serials ps 
                               LEFT JOIN borrowings b ON ps.id = b.serial_id AND b.returned_at IS NULL
                               LEFT JOIN users u ON b.borrower_id = u.id 
                               WHERE ps.product_id = ?
                               ORDER BY ps.serial_code ASC");
        $stmt->execute([$data['product_id']]);
        $serials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For available items, try to get the last known details
        foreach ($serials as &$s) {
            if (!$s['asset_number']) {
                $last_borrow = $pdo->prepare("SELECT asset_number, building, floor, department FROM borrowings WHERE serial_id = ? ORDER BY id DESC LIMIT 1");
                $last_borrow->execute([$s['id']]);
                $last_data = $last_borrow->fetch(PDO::FETCH_ASSOC);
                if ($last_data) {
                    $s['asset_number'] = $last_data['asset_number'] ?: '';
                    $s['building'] = $last_data['building'] ?: '';
                    $s['floor'] = $last_data['floor'] ?: '';
                    $s['department'] = $last_data['department'] ?: '';
                }
            }
        }
        
        echo json_encode(['success' => true, 'serials' => $serials]);
    }

    // ===== CHECK SKU =====
    elseif ($action === 'check_sku') {
        $sql = "SELECT id, name FROM products WHERE sku = ?";
        $params = [$data['sku']];
        if (!empty($data['exclude_id'])) {
            $sql .= " AND id != ?";
            $params[] = $data['exclude_id'];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'exists' => $product ? true : false, 'product' => $product]);
    }

    // ===== CHECK SERIAL =====
    elseif ($action === 'check_serial') {
        $stmt = $pdo->prepare("SELECT ps.id, p.name FROM product_serials ps JOIN products p ON ps.product_id = p.id WHERE ps.serial_code = ?");
        $stmt->execute([$data['serial']]);
        $serial = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'exists' => $serial ? true : false, 'serial' => $serial]);
    }

    else {
        echo json_encode(['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
