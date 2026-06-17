<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->prepare("SELECT p.price_rent FROM products p LIMIT 1");
    $stmt->execute();
    echo "Success: price_rent exists.";
} catch(Exception $e) {
    echo "Error checking price_rent: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT p.rental_price FROM products p LIMIT 1");
    $stmt->execute();
    echo "Success: rental_price exists.";
} catch(Exception $e) {
    echo "Error checking rental_price: " . $e->getMessage() . "\n";
}
