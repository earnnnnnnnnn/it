<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}
header('Location: index.php');
exit;
?>
