<?php
require_once 'config/db.php';
$output = "";
try {
    $stmt = $pdo->prepare("SELECT p.price_rent FROM products p LIMIT 1");
    $stmt->execute();
    $output .= "Success: price_rent exists.\n";
} catch(Exception $e) {
    $output .= "Error checking price_rent: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT p.rent_price FROM products p LIMIT 1");
    $stmt->execute();
    $output .= "Success: rent_price exists.\n";
} catch(Exception $e) {
    $output .= "Error checking rent_price: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT p.rental_price FROM products p LIMIT 1");
    $stmt->execute();
    $output .= "Success: rental_price exists.\n";
} catch(Exception $e) {
    $output .= "Error checking rental_price: " . $e->getMessage() . "\n";
}

file_put_contents('test_db_output.txt', $output);
