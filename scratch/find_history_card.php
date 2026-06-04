<?php
$file = 'c:/xampp/htdocs/it/pages/borrow.php';
$lines = file($file);
foreach ($lines as $i => $line) {
    if (stripos($line, 'timeline') !== false || stripos($line, 'history') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
?>
