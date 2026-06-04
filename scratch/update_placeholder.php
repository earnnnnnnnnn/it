<?php
$file = 'c:/xampp/htdocs/it/pages/borrow.php';
$content = file_get_contents($file);

$content = str_replace('placeholder="กรอกเลขครุภัณฑ์ หรือเลขที่เอกสาร..."', 'placeholder="7440-001-0001-60-0012"', $content);

file_put_contents($file, $content);
echo "Success";
?>
