<?php
$file = 'c:/xampp/htdocs/it/pages/settings.php';
$content = file_get_contents($file);

// Fix the missing label
$badHtml = '<div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                
                            </div>';
$goodHtml = '<div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($r[\'label\']) ?></span>
                            </div>';

$content = str_replace($badHtml, $goodHtml, $content);

file_put_contents($file, $content);
echo "Success";
?>
