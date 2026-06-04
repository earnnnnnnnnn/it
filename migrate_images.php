<?php
require_once 'config/db.php';

echo "Starting migration...\n";

// Create directories if they don't exist
if (!is_dir('assets/images/borrows')) {
    mkdir('assets/images/borrows', 0777, true);
    echo "Created borrows directory.\n";
}

// 1. Move borrow images
$stmt = $pdo->query("SELECT id, image FROM borrowings WHERE image LIKE 'borrow_%'");
$borrow_count = 0;
while ($row = $stmt->fetch()) {
    $old_path = 'assets/images/' . $row['image'];
    $new_name = 'borrows/' . $row['image'];
    $new_path = 'assets/images/' . $new_name;
    
    if (file_exists($old_path)) {
        if (rename($old_path, $new_path)) {
            $pdo->prepare("UPDATE borrowings SET image = ? WHERE id = ?")->execute([$new_name, $row['id']]);
            echo "Moved: " . $row['image'] . " -> " . $new_name . "\n<br>";
            $borrow_count++;
        }
    }
}
echo "Migrated $borrow_count borrow images.\n<br>";

// 2. Move user images
if (!is_dir('assets/images/users')) {
    mkdir('assets/images/users', 0777, true);
    echo "Created users directory.\n<br>";
}

$stmt_user = $pdo->query("SELECT id, image FROM users WHERE image LIKE 'user_%'");
$user_count = 0;
while ($row = $stmt_user->fetch()) {
    $old_path = 'assets/images/' . $row['image'];
    $new_name = 'users/' . $row['image'];
    $new_path = 'assets/images/' . $new_name;
    
    if (file_exists($old_path)) {
        if (rename($old_path, $new_path)) {
            $pdo->prepare("UPDATE users SET image = ? WHERE id = ?")->execute([$new_name, $row['id']]);
            echo "Moved: " . $row['image'] . " -> " . $new_name . "\n<br>";
            $user_count++;
        }
    }
}
echo "Migrated $user_count user images.\n<br>";

if (file_exists('assets/images/default_user.png') && !file_exists('assets/images/users/default_user.png')) {
    copy('assets/images/default_user.png', 'assets/images/users/default_user.png');
}

echo "Migration completed!\n";
?>
