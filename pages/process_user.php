<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if ($_SESSION['user_role'] != 'ADMIN') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $role = $_POST['role'];

    // Split name and generate username
    $parts = explode(' ', $fullname, 2);
    $firstname = $parts[0];
    $lastname = isset($parts[1]) ? $parts[1] : '';
    $username = strtolower(str_replace(' ', '', $firstname)) . rand(100, 999);

    // Check if email exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        header('Location: users.php?error=email_exists');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$username, $firstname, $lastname, $email, $password, $role])) {
        header('Location: users.php?success=1');
    } else {
        header('Location: users.php?error=failed');
    }
}
