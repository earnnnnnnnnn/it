<?php
$file = 'c:/xampp/htdocs/it/pages/borrow.php';
$content = file_get_contents($file);

$oldButton = '<button type="button" class="btn btn-info text-white px-3" onclick="viewHistory()" title="ดูประวัติการเบิก">
                            <i class="fas fa-history"></i>
                        </button>';

$content = str_replace($oldButton, '', $content);

file_put_contents($file, $content);
echo "Success";
?>
