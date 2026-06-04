<?php
$headerFile = 'c:/xampp/htdocs/it/includes/header.php';
$settingsFile = 'c:/xampp/htdocs/it/pages/settings.php';

// 1. Update Header (Add SortableJS)
$headerContent = file_get_contents($headerFile);
if (strpos($headerContent, 'Sortable.min.js') === false) {
    $headerContent = str_replace('</head>', '    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>', $headerContent);
    file_put_contents($headerFile, $headerContent);
}

// 2. Update Settings.php (Query)
$settingsContent = file_get_contents($settingsFile);
$settingsContent = str_replace("ORDER BY id ASC", "ORDER BY sort_order ASC, id ASC", $settingsContent);

// 3. Add Drag Handles to List Items
$itemOld = '<div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2" id="reason-<?= $r[\'id\'] ?>">';
$itemNew = '<div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-2 reason-item" id="reason-<?= $r[\'id\'] ?>" data-id="<?= $r[\'id\'] ?>">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-grip-vertical text-muted me-3 drag-handle" style="cursor: move;"></i>
                                <span class="fw-bold"><?= htmlspecialchars($r[\'label\']) ?></span>
                            </div>';
$settingsContent = str_replace($itemOld, $itemNew, $settingsContent);

// Remove the old span that was inside the old item
$settingsContent = str_replace('<span class="fw-bold"><?= htmlspecialchars($r[\'label\']) ?></span>', '', $settingsContent);

file_put_contents($settingsFile, $settingsContent);
echo "Success";
?>
