<?php
$file = 'c:/xampp/htdocs/it/pages/settings.php';
$content = file_get_contents($file);

$sortableJS = '
        // Initialize Sortable for Reasons
        const initSortable = (id) => {
            const el = document.getElementById(id);
            if (el) {
                new Sortable(el, {
                    animation: 150,
                    handle: ".drag-handle",
                    onEnd: function() {
                        const order = [];
                        $("#" + id + " .reason-item").each(function() {
                            order.push($(this).data("id"));
                        });
                        saveOrder(order);
                    }
                });
            }
        };

        const saveOrder = (order) => {
            $.ajax({
                url: "ajax_reorder_reasons.php",
                method: "POST",
                data: JSON.stringify({ order: order }),
                contentType: "application/json",
                success: function(res) {
                    const result = JSON.parse(res);
                    if (!result.success) {
                        Swal.fire({ icon: "error", title: "ไม่สามารถบันทึกลำดับได้", text: result.message });
                    }
                }
            });
        };

        initSortable("borrowReasonList");
        initSortable("importReasonList");
';

$content = str_replace('$(document).ready(function() {', "$(document).ready(function() {\n" . $sortableJS, $content);

// Also need to make sure importReasonList has the container ID (it might be missing if I only updated borrow list in setup_reordering.php)
$content = str_replace('<div id="importReasonList">', '<div id="importReasonList" class="reorder-list">', $content);
$content = str_replace('<div id="borrowReasonList">', '<div id="borrowReasonList" class="reorder-list">', $content);

file_put_contents($file, $content);
echo "Success";
?>
